<?php
/**
 * Class Auth to handle login with cookies and authorization to conotrollers
 * and actions.
 */

namespace Primer\Component;

use Primer\Core\Primer;

class AuthComponent extends Component
{
    /////////////////////////////////////////////////
    // PROPERTIES, PUBLIC
    /////////////////////////////////////////////////

    /*
     * Session instance
     */
    public $session;

    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE
    /////////////////////////////////////////////////

    /*
     * Configuration for: Hashing strength
     * This is the place where you define the strength of your password hashing/salting
     *
     * To make password encryption very safe and future-proof, the PHP 5.5 hashing/salting functions
     * come with a clever so called COST FACTOR. This number defines the base-2 logarithm of the rounds of hashing,
     * something like 2^12 if your cost factor is 12. By the way, 2^12 would be 4096 rounds of hashing, doubling the
     * round with each increase of the cost factor and therefore doubling the CPU power it needs.
     * Currently, in 2013, the developers of this functions have chosen a cost factor of 10, which fits most standard
     * server setups. When time goes by and server power becomes much more powerful, it might be useful to increase
     * the cost factor, to make the password hashing one step more secure. Have a look here
     * (@see https://github.com/panique/php-users/wiki/Which-hashing-&-salting-algorithm-should-be-used-%3F)
     * in the BLOWFISH benchmark table to get an idea how this factor behaves. For most people this is irrelevant,
     * but after some years this might be very very useful to keep the encryption of your database up to date.
     *
     * Remember: Every time a user registers or tries to log in (!) this calculation will be done.
     * Don't change this if you don't know what you do.
     *
     * To get more information about the best cost factor please have a look here
     * @see http://stackoverflow.com/q/4443476/1114320
     */
    private $_hashCostFactor = 10;

    private $_allowedActions = array();

    /**
     * Initialize Component
     */
    public function __construct(SessionComponent $session)
    {
        $this->session = $session;
        $this->loginWithCookie();
    }

    /**
     * Function determine which actions can be accessed without the
     * user needing to be logged into the system.
     *
     * @param array $actions
     *
     * @return bool true if render can access action without login
     */
    public function allow($actions = array())
    {
        if (is_string($actions)) {
            $this->_allowedActions[] = $actions;
        }
        else if (is_array($actions)) {
            array_merge($this->_allowedActions, $actions);
        }

        if (in_array(Primer::getValue('action'), $actions)) {
            return true;
        }

        if ($this->session->isUserLoggedIn()) {
            return true;
        }
        $this->session->setFlash('You must be logged in to do that', 'notice');
        $referrer = $_SERVER['REQUEST_URI'];
        Router::redirect('/login/?forward_to=' . htmlspecialchars($referrer, ENT_QUOTES, 'utf-8'));
    }

    public function login($model)
    {
        foreach ($model as $key => $val) {
            if (array_key_exists($key, $model->getSchema())) {
                $this->session->write('Auth.' . $key, $val);
            }
        }
    }

    public function logout()
    {
        $this->session->delete('Auth');
    }

    /**
     * Logs user in if cookie value matches database value
     *
     * @return bool
     */
    public function loginWithCookie()
    {
        $cookie = isset($_COOKIE['rememberme']) ? $_COOKIE['rememberme'] : '';

        if ($cookie) {
            list ($user_id, $token, $hash) = explode(':', base64_decode($cookie));

            if ($hash !== hash('sha256', $user_id . ':' . $token)) {
                return false;
            }

            // do not log in when token is empty
            if (empty($token)) {
                return false;
            }

            // @TODO: need to find a better way to tie this in without using global User
            $user = new \User();
            $user = $user->findById($user_id);

            if ($user->rememberme_token == $token) {
                $this->login($user);
                return true;
            }
            else {
                setcookie('rememberme', false, time() - (3600 * 3650), '/', DOMAIN);
                $this->logout();
            }
        }

        return false;
    }

    /**
     * crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
     * the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
     * compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
     * want the parameter: as an array with, currently only used with 'cost' => XX.
     *
     * @param $string
     *
     * @return bool|false|string
     */
    public function hash($string)
    {
        return password_hash($string, PASSWORD_DEFAULT, array('cost' => $this->_hashCostFactor));
    }

    public function verifyHash($string, $hash)
    {
        return password_verify($string, $hash);
    }
}
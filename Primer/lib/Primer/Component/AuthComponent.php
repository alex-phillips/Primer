<?php
/**
 * Class Auth to handle login with cookies and authorization to controllers
 * and actions.
 */

namespace Primer\Component;

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
    private $_initialized = false;

    /**
     * Initialize Component
     */
    public function __construct(SessionComponent $session)
    {
        $this->session = $session;
        $this->loginWithCookie();
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
            list ($user_id, $token, $hash) = explode(
                ':',
                base64_decode($cookie)
            );

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
            } else {
                setcookie(
                    'rememberme',
                    false,
                    time() - (3600 * 3650),
                    '/',
                    DOMAIN
                );
                $this->logout();
            }
        }

        return false;
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

    public function run($action)
    {
        if (!$this->_initialized) {
            return true;
        }

        if (in_array($action, $this->_allowedActions)) {
            return true;
        }

        if ($this->session->isUserLoggedIn()) {
            return true;
        }

        return false;
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
        $this->_initialized = true;

        if (is_array($actions)) {
            $this->_allowedActions = array_merge(
                $this->_allowedActions,
                $actions
            );
        } else {
            $this->_allowedActions[] = $actions;
        }
    }
}
<?php
/**
 * Class Auth to handle login with cookies and authorization to conotrollers
 * and actions.
 */

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

    private $_allowedActions = array();

    /**
     * Initialize Component
     */
    protected function __construct()
    {
        $this->session = SessionComponent::getInstance();
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

            $user = new User();
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
        return password_hash($string, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR));
    }

    public function verifyHash($string, $hash)
    {
        return password_verify($string, $hash);
    }
}
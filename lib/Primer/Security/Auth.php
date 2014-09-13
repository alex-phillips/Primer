<?php
/**
 * Class Auth to handle login with cookies and authorization to controllers
 * and actions.
 */

namespace Primer\Security;

use Primer\Core\Object;
use Primer\Session\Session;

class Auth extends Object
{
    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE
    /////////////////////////////////////////////////

    /*
     * Session instance
     */
    private $_session;

    /*
     * Actions permitted in the current controller with having to authenticate
     */
    private $_allowedActions = array();

    private $_initialized = false;

    /**
     * Initialize Component
     */
    public function __construct(Session $session)
    {
        $this->_session = $session;
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
                $this->_session->write('Auth.' . $key, $val);
            }
        }
    }

    public function logout()
    {
        setcookie('rememberme', false, time() - (3600 * 3650), '/', DOMAIN);
        $this->_session->delete('Auth');
    }

    public function run($action)
    {
        if (!$this->_initialized) {
            return true;
        }

        if (in_array($action, $this->_allowedActions)) {
            return true;
        }

        if ($this->_session->isUserLoggedIn()) {
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
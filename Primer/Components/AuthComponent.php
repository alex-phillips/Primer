<?php
/**
 * Class Auth to handle login with cookies and authorization to conotrollers
 * and actions.
 */

class AuthComponent extends Component
{
    /**
     * Initialize Component
     */
    protected function __construct()
    {
        $this->Session = SessionComponent::getInstance();
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
        if (in_array(Primer::getValue('action'), $actions)) {
            return true;
        }

        if ($this->Session->isUserLoggedIn()) {
            return true;
        }
        $this->Session->setFlash('You must be logged in to do that', 'warning');
        Router::redirect('/');
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
                $this->Session->write($user);
                $this->Session->write('user_logged_in', true);
                return true;
            }
            else {
                setcookie('rememberme', false, time() - (3600 * 3650), '/', DOMAIN);
                $this->Session->destroy();
            }
        }

        return false;
    }
}
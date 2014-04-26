<?php
/**
 * Class Auth to handle login with cookies and authorization to conotrollers
 * and actions.
 */

class Auth
{
    /**
     * Initialize auth static class with methods that need to run for
     * every render.
     */
    public static function init()
    {
        self::loginWithCookie();
    }

    /**
     * Function determine which actions can be accessed without the
     * user needing to be logged into the system.
     *
     * @param array $actions
     *
     * @return bool true if render can access action without login
     */
    public static function allow($actions = array())
    {
        if (in_array(Primer::getValue('action'), $actions)) {
            return true;
        }

        if (Session::isUserLoggedIn()) {
            return true;
        }
        Session::setFlash('You must be logged in to do that', 'warning');
        Router::redirect('/');
    }

    /**
     * Logs user in if cookie value matches database value
     *
     * @return bool
     */
    public static function loginWithCookie()
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
                Session::write($user);
                Session::write('user_logged_in', true);
                return true;
            }
            else {
                setcookie('rememberme', false, time() - (3600 * 3650), '/', DOMAIN);
                Session::destroy();
            }
        }

        return false;
    }
}
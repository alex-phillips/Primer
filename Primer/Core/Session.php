<?php
/**
 * @auther Alex Phillips
 * Date: 10/9/13
 * Time: 5:28 PM
 */

class Session
{
    private static $_flash_messages = array();

    public static function init()
    {
        // if no session exist, start the session
        if (session_id() == '') {
            session_start();
        }
    }

    /**
     * Destroy the session and unset all session values
     */
    public static function destroy()
    {
        if (session_id() != '') {
            session_destroy();
            foreach ($_SESSION as $k => $v) {
                self::delete($k);
            }
        }
    }

    /**
     * Used to write a value to a session key
     *
     * @param $key
     * @param null $value
     */
    public static function write($key, $value = null)
    {
        if (is_array($key) || is_object($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }

            return;
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Used to reada  value from a given session key
     *
     * @param mixed $key Usually a string, right ?
     * @return mixed
     */
    public static function read($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return null;
    }

    /**
     * Delete a session key
     *
     * @param $key
     */
    public static function delete($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Set flash message to be outputted to user on the next view rendered.
     * Can pass in a type to apply CSS styles (i.e. success, failure, warning)
     *
     * @param $message
     * @param string $class
     */
    public static function setFlash($message, $class = '')
    {
        self::$_flash_messages[$message] = $class;
        self::write('flash_messages', self::$_flash_messages);
    }

    /**
     * Control browser redirects
     * @depricated use controller redirect function
     * @param $header
     */
    public static function redirect($header)
    {
        self::write('messages', self::$_flash_messages);
        header("Location: " . $header);
        exit;
    }

    /**
     * @return bool|mixed
     * @depricated
     */
    public static function isUserLoggedIn()
    {
        if (self::read('user_logged_in') != null) {
            return self::read('user_logged_in');
        }
        return false;
    }

    /**
     * Return true if the user's
     *
     * @return bool
     * @depricated
     */
    public static function isAdmin()
    {
        if (self::read('role') == 'admin') {
            return true;
        }

        if (self::read('username') == 'admin') {
            return true;
        }

        return false;
    }
}
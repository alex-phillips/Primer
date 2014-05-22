<?php
/**
 * @auther Alex Phillips
 * Date: 10/9/13
 * Time: 5:28 PM
 */

class SessionComponent extends Component
{
    private $_flashMessages = array();

    protected function __construct()
    {
        // If no session exist, start the session
        if (session_id() === '') {
            session_start();
        }
    }

    /**
     * Destroy the session and unset all session values
     */
    public function destroy()
    {
        if (session_id() != '') {
            foreach ($_SESSION as $k => $v) {
                $this->delete($k);
            }
            session_destroy();
        }
    }

    /**
     * Used to write a value to a session key
     *
     * @param $key
     * @param null $value
     */
    public function write($key, $value = null)
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
    public function read($key)
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
    public function delete($key)
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
    public function setFlash($message, $class = '')
    {
        // @TODO: maybe we can skip variable and write straight to $_SESSION
        $this->_flashMessages[$message] = $class;
        $this->write('flash_messages', $this->_flashMessages);
    }

    /**
     * Control browser redirects
     * @depricated use Router redirect function
     * @param $header
     */
    public function redirect($header)
    {
        $this->write('messages', $this->_flashMessages);
        Router::redirect($header);
    }

    /**
     * @return bool|mixed
     * @depricated
     */
    public function isUserLoggedIn()
    {
        if ($this->read('user_logged_in') != null) {
            return $this->read('user_logged_in');
        }
        return false;
    }

    /**
     * Return true if the user's
     *
     * @return bool
     * @depricated
     */
    public function isAdmin()
    {
        if ($this->read('role') == 'admin') {
            return true;
        }

        if ($this->read('username') == 'admin') {
            return true;
        }

        return false;
    }
}
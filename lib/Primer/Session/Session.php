<?php
/**
 * @auther Alex Phillips
 * Date: 10/9/13
 * Time: 5:28 PM
 */

namespace Primer\Session;

use Primer\Core\Object;

class Session extends Object
{
    private $_sessionContainer;
    /*
     * Array that contains system messages
     */
    private $_flashMessages = array();

    /**
     * Class constructor - initializes session of one is not currently active
     */
    public function __construct()
    {
        // If no session exist, start the session
        if (session_id() === '') {
            session_start();
        }

        $this->_sessionContainer = new SessionBag($_SESSION);
    }

    /**
     * Destroy the session and unset all session values
     */
    public function destroy()
    {
        if (session_id() != '') {
            foreach ($this->_sessionContainer as $k => $v) {
                $this->delete($k);
            }
            session_destroy();
        }
    }

    /**
     * Delete a session key
     *
     * @param $key
     */
    public function delete($key)
    {
        $this->_sessionContainer->clear($key);
    }

    /**
     * Set flash message to be outputted to user on the next view rendered.
     * Can pass in a type to apply CSS styles (i.e. success, failure, warning)
     *
     * @param $messages
     * @param string $class
     */
    public function setFlash($messages, $class = '')
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $this->_flashMessages[$message] = $class;
                $this->write('flash_messages', $this->_flashMessages);
            }
        } else {
            $this->_flashMessages[$messages] = $class;
        }
        $this->write('flash_messages', $this->_flashMessages);
    }

    /**
     * Function to format and return system messages to display in the view
     *
     * @return string
     */
    public function flash()
    {
        $markup = '';
        if ($this->read('flash_messages')) {
            foreach ($this->read(
                'flash_messages'
            ) as $error => $class) {
                $markup .= "<div class='system-message $class'>" . $error . "</div>";
            }
        }

        $this->delete('flash_messages');

        return $markup;
    }

    /**
     * Used to write a value to a session key
     *
     * @param $key
     * @param null $value
     */
    public function write($key, $value = null)
    {
        $this->_sessionContainer->set($key, $value);
    }

    /**
     * Function that returns true if user is currently logged in, otherwise, false
     *
     * @return bool|mixed
     * @depricated
     */
    public function isUserLoggedIn()
    {
        if ($this->read('Auth')) {
            return true;
        }

        return false;
    }

    /**
     * Used to reada  value from a given session key
     *
     * @param mixed $key Usually a string, right ?
     *
     * @return mixed
     */
    public function read($key)
    {
        return $this->_sessionContainer->get($key);
    }

    /**
     * Return true if the user's 'role' is 9 or more
     *
     * @return bool
     * @depricated
     */
    public function isAdmin()
    {
        if ($this->read('Auth.role') >= 9) {
            return true;
        }

        if ($this->read('Auth.username') == 'admin') {
            return true;
        }

        return false;
    }
}
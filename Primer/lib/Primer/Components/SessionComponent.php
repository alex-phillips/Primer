<?php
/**
 * @auther Alex Phillips
 * Date: 10/9/13
 * Time: 5:28 PM
 */

namespace Primer\Components;

use Primer\Utility\ParameterContainer;

class SessionComponent extends Component
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

        $this->_sessionContainer = new SessionContainer($_SESSION);
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
     * Used to reada  value from a given session key
     *
     * @param mixed $key Usually a string, right ?
     * @return mixed
     */
    public function read($key)
    {
        return $this->_sessionContainer->get($key);
    }

    /**
     * Delete a session key
     *
     * @param $key
     */
    public function delete($key)
    {
        $this->_sessionContainer->delete($key);
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
        }
        else {
            $this->_flashMessages[$messages] = $class;
        }
        $this->write('flash_messages', $this->_flashMessages);
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

/**
 * Class SessionContainer
 *
 * Modified ParameterContainer that passes the parameters by reference. This allows
 * the $_SESSION global to be modified as the class modifies the parameters inside
 * of the class.
 */
class SessionContainer extends ParameterContainer
{
    public function __construct(&$parameters)
    {
        $this->_parameters = &$parameters;
    }
}
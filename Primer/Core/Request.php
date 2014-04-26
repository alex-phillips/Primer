<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:53 PM
 */

class Request
{
    private $_request_method;

    // TODO: should this be a singleton?
    private function __construct()
    {
        foreach ($_REQUEST as $key => $value) {
            $this->$key = $value;
        }

        $this->_request_method = $_SERVER['REQUEST_METHOD'];
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    public function is($type)
    {
        if (strcasecmp($this->_request_method, $type) == 0) {
            return true;
        }

        return false;
    }
}
<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:53 PM
 */

class RequestComponent extends Component
{
    private $_requestMethod;
    public $data;

    protected function __construct()
    {
        foreach ($_REQUEST as $key => $value) {
            $this->$key = $value;
        }

        $this->_requestMethod = $_SERVER['REQUEST_METHOD'];
    }

    public function is($type)
    {
        if (strcasecmp($this->_requestMethod, $type) == 0) {
            return true;
        }

        return false;
    }
}
<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:53 PM
 */

namespace Primer\Component;

use Primer\Utility\ParameterContainer;

class RequestComponent extends Component
{
    private $_requestMethod;
    public $post = array();
    public $query = array();
    public $files = array();

    public function __construct()
    {
        $this->post = new ParameterContainer($_POST);
        $this->query = new ParameterContainer($_GET);

        if (isset($_FILES) && !empty($_FILES)) {
            foreach ($_FILES as $name => $info) {
                $this->files[$name] = $info;
            }
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

    public function post()
    {
        return $this->post;
    }

    public function query()
    {
        return $this->query;
    }

    public function files()
    {
        return $this->files;
    }
}
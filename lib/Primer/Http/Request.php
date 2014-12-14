<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:53 PM
 */

namespace Primer\Http;

use Primer\Core\Object;
use Primer\Routing\Route;
use Primer\Utility\ParameterBag;

class Request extends Object
{
    public $post = array();
    public $query = array();
    public $files = array();
    public $params = array();
    public $controller;
    public $action;
    private $_requestMethod;

    public function __construct(Route $route)
    {
        $this->params = $route->getParameters();
        $this->params['pass'] = array();

        foreach ($this->params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
            else {
                $this->params['pass'][$key] = $value;
            }
        }

        $this->post = new ParameterBag($_POST);
        $this->query = new ParameterBag($_GET);

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

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }
}
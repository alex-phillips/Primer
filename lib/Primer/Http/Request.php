<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:53 PM
 */

namespace Primer\Http;

use Primer\Core\Object;
use Primer\Utility\ParameterBag;

class Request extends Object
{
    public $url = '';
    public $requestMethod;
    public $post = array();
    public $query = array();
    public $files = array();
    public $params;

    public function __construct($url = '', $params = array())
    {
        $this->parseRequestUrl($url);

        $this->params = new ParameterBag(array(
                'controller' => '',
                'action'     => '',
                'pass'       => array(),
            ));

        if ($params) {
            $this->addParams($params);
        }

        $this->post = new ParameterBag($_POST);
        $this->query = new ParameterBag($_GET);

        if (isset($_FILES) && !empty($_FILES)) {
            foreach ($_FILES as $name => $info) {
                $this->files[$name] = $info;
            }
        }
    }

    public function is($type)
    {
        if (strcasecmp($this->requestMethod, $type) == 0) {
            return true;
        }

        return false;
    }

    public function here($base = true)
    {
        $url = $this->url;
        if (!empty($this->query)) {
            $url .= http_build_query($this->query, null, '&');
        }

        return $url;
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
        return $this->params->get('controller');
    }

    public function getAction()
    {
        return $this->params->get('action');
    }

    public function setRequestUrl($url)
    {
        $this->parseRequestUrl($url);
    }

    public function addParams($params)
    {
        foreach ($params as $k => $v) {
            if ($this->params->has($k)) {
                $this->params[$k] = $v;
            }
            else {
                $this->params["pass.$k"] = $v;
            }
        }
    }

    private function parseRequestUrl($url = '')
    {
        $this->requestMethod = (isset($_POST['_method']) && ($_method = strtoupper($_POST['_method'])) && in_array($_method,array('PUT','DELETE'))) ? $_method : $_SERVER['REQUEST_METHOD'];

        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }

        // strip GET variables from URL
        if (($pos = strpos($url, '?')) !== false) {
            $url =  substr($url, 0, $pos);
        }

        $this->url = $url;
    }
}
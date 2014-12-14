<?php

namespace Primer\Routing;

use Primer\Core\Object;

class Route extends Object
{
    /**
     * URL of this Route
     *
     * @var string
     */
    private $_url;

    /**
     * Accepted HTTP methods for this route
     *
     * @var array
     */
    private $_methods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * Target for this route, can be anything.
     *
     * @var mixed
     */
    private $_target;

    /**
     * The name of this route, used for reversed routing
     *
     * @var string
     */
    private $_name;

    /**
     * Custom parameter filters for this route
     *
     * @var array
     */
    private $_filters = array();

    /**
     * Array containing parameters passed through request URL
     *
     * @var array
     */
    private $_parameters = array();

    /**
     * @param       $resource
     * @param array $config
     */
    public function __construct($resource, array $config)
    {
        $this->setUrl($resource);
        $this->setParameters($config);
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function setUrl($url)
    {
        $url = (string)$url;

        // make sure that the URL is suffixed with a forward slash
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        $this->_url = $url;
    }

    public function getTarget()
    {
        return $this->_target;
    }

    public function setTarget($target)
    {
        $this->_target = $target;
    }

    public function getMethods()
    {
        return $this->_methods;
    }

    public function setMethods(array $methods)
    {
        $this->_methods = $methods;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = (string)$name;
    }

    public function setFilters(array $filters)
    {
        $this->_filters = $filters;
    }

    public function getRegex()
    {
        return preg_replace_callback(
            array(
                "/:(\w+)/",
                "/(\*)/",
                "/(\*\*)/",
            ), array(&$this, 'substituteFilter'), $this->_url
        );
    }

    public function getConfig()
    {
        return $this->_config;
    }

    private function substituteFilter($matches)
    {
        if (isset($matches[1]) && isset($this->_filters[$matches[1]])) {
            return $this->_filters[$matches[1]];
        }

        if ($matches[1] === '*') {
            return "?([^\/]*?)";
        }

        if ($matches[1] === '**') {
            return "?(.*?)";
        }

        return "([^\/]+)";
    }

    public function getParameter($key)
    {
        if (isset($this->_parameters[$key])) {
            return $this->_parameters[$key];
        }

        return null;
    }

    public function getParameters()
    {
        return $this->_parameters;
    }

    public function setParameters(array $parameters)
    {
        $this->_parameters = $parameters;
    }

    public function addParameters(array $parameters)
    {
        $this->_parameters = array_merge($this->_parameters, $parameters);
    }

//    public function dispatch()
//    {
//        $this->_controller = $this->getControllerName($this->_controller);
//        $instance = new $this->_controller;
//        call_user_func_array(array($instance, $this->_action), $this->_parameters);
//    }

//    public function getController()
//    {
//        return $this->_controller;
//    }
//
//    public function getAction()
//    {
//        return $this->_action;
//    }

    public function getParams()
    {
        return $this->_parameters;
    }
}
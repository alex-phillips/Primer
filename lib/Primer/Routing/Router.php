<?php
/**
 * @author Alex Phillips
 * Date: 3/8/14
 * Time: 2:38 PM
 */

namespace Primer\Routing;

use Primer\Core\Object;

class Router extends Object
{
    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    private $_url = array();
    private $_routes = array();

    private $_controller = 'pages';
    private $_action = 'index';
    private $_args = array();

    public function __construct()
    {
        $this->_url = $this->parseUrl();
    }

    private function parseUrl($url = null)
    {
        if (!$url) {
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        }
        $components = parse_url($url);
        $url = trim($components['path'], '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if ($url) {
            return explode('/', $url);
        }
        return array();
    }

    public function dispatch()
    {
        foreach ($this->_routes as $path => $route) {
            $path = $this->parseUrl($path);

            if ($path === $this->_url && empty($path)) {
                $this->_controller = $route['controller'];
                $this->_action = $route['action'];
                if (sizeof($this->_url) > 2) {
                    $this->_args = array_slice($this->_url, 2);
                }

                $route['args'] = $this->_args;

                return $route;
            }

            $tmp = array();

            if (isset($path[0])) {
                if (!isset($this->_url[0])) {
                    continue;
                }
                if (preg_match('#:.*#', $path[0], $matches)) {
                    $tmp[str_replace(':', '', $matches[0])] = $this->_url[0];
                }
                else {
                    if ($path[0] !== $this->_url[0]) {
                        continue;
                    }
                }
            }
            else {
                if (isset($this->_url[0])) {
                    continue;
                }
            }
            if (isset($path[1])) {
                if (!isset($this->_url[1])) {
                    continue;
                }
                if (preg_match('#:.*#', $path[1], $matches)) {
                    $tmp[str_replace(':', '', $matches[0])] = $this->_url[1];
                }
                else {
                    if ($path[1] !== $this->_url[1]) {
                        continue;
                    }
                }
            }
            else {
                if (isset($this->_url[1])) {
                    continue;
                }
            }

            if (is_array($route)) {
                $args = array();
                foreach (array_keys($route) as $key) {
                    if (is_int($key)) {
                        if (array_key_exists($route[$key], $tmp)) {
                            $args[] = $tmp[$route[$key]];
                        }
                        else {
                            $args[] = $route[$key];
                        }
                    }
                }

                $this->_controller = $route['controller'];
                $this->_action = $route['action'];
                $this->_args = $args;
                $route['args'] = $args;
            }

            return $route;
        }

        if ($this->_url) {
            $this->_controller = $this->_url[0];
            if (sizeof($this->_url) === 1 || preg_match('#\?.+#', $this->_url[1]) || preg_match('#.+:.+#', $this->_url[1])) {
                $this->_action = 'index';
                $args = array_slice($this->_url, 1);
            }
            else {
                $this->_action = $this->_url[1];
                $args = array_slice($this->_url, 2);
            }
            /*
             * Check for arguments. If an argument is passed with a colon,
             * ex: page:1, then match it as a key-value pair. Otherwise, add
             * it as an index.
             */
            foreach ($args as $arg) {
                if (preg_match('#.+:.+#', $arg)) {
                    // @TODO: why isn't this setting any significant varialbes? Check this out.
                    list($key, $value) = explode(':', $arg);
                }
                else {
                    $this->_args[] = $arg;
                }
            }
        }

        return array(
            'controller' => $this->_controller,
            'action'     => $this->_action,
            'args'       => $this->_args,
        );
    }

    public function route($path, $params)
    {
        $this->_routes[$path] = $params;
    }

    /**
     * Control browser redirects
     *
     * @param $location
     */
    public function redirect($location)
    {
        if ($location === 'referrer') {
            $location = isset($_SERVER['HTTP_REFERRER']) ? $_SERVER['HTTP_REFERRER'] : '/';
        }
        header("Location: " . $location);
        exit;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getArgs()
    {
        return $this->_args;
    }
}
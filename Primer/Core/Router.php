<?php
/**
 * @author Alex Phillips
 * Date: 3/8/14
 * Time: 2:38 PM
 */

require_once(APP_ROOT . '/Config/routes.php');

class Router
{
    /////////////////////////////////////////////////
    // PROPERTIES, PUBLIC
    /////////////////////////////////////////////////

    public static $controller = 'pages';
    public static $action = 'index';
    public static $args = array();

    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    private static $_url = array();
    private static $_routes = array();

    public static function dispatch()
    {
        self::$_url = self::_parseURL();
        foreach (self::$_routes as $path => $route) {
            $path = self::_parseURL($path);

            if ($path === self::$_url && empty($path)) {
                self::$controller = $route['controller'];
                self::$action = $route['action'];
                if (sizeof(self::$_url) > 2) {
                    self::$args = array_slice(self::$_url, 2);
                }
                return;
            }

            $tmp = array();

            if (isset($path[0])) {
                if (!isset(self::$_url[0])) {
                    continue;
                }
                if (preg_match('#:.*#', $path[0], $matches)) {
                    $tmp[str_replace(':', '', $matches[0])] = self::$_url[0];
                }
                else if ($path[0] !== self::$_url[0]) {
                    continue;
                }
            }
            else if (isset(self::$_url[0])) {
                continue;
            }
            if (isset($path[1])) {
                if (!isset(self::$_url[1])) {
                    continue;
                }
                if (preg_match('#:.*#', $path[1], $matches)) {
                    $tmp[str_replace(':', '', $matches[0])] = self::$_url[1];
                }
                else if ($path[1] !== self::$_url[1]) {
                    continue;
                }
            }
            else if (isset(self::$_url[1])) {
                continue;
            }

            $args = array();
            foreach (array_keys($route) as $key) {
                if(is_int($key)) {
                    if (array_key_exists($route[$key], $tmp)) {
                        $args[] = $tmp[$route[$key]];
                    }
                    else {
                        $args[] = $route[$key];
                    }
                }
            }

            self::$controller = $route['controller'];
            self::$action = $route['action'];
            self::$args = $args;
            return;
        }

        if (self::$_url) {
            self::$controller = self::$_url[0];
            if (sizeof(self::$_url) === 1 || preg_match('#\?.+#', self::$_url[1]) ||  preg_match('#.+:.+#', self::$_url[1])) {
                self::$action = 'index';
                $args = array_slice(self::$_url, 1);
            }
            else {
                self::$action = self::$_url[1];
                $args = array_slice(self::$_url, 2);
            }
            /*
             * Check for arguments. If an argument is passed with a colon,
             * ex: page:1, then match it as a key-value pair. Otherwise, add
             * it as an index.
             */
            foreach ($args as $arg) {
                if (preg_match('#.+:.+#', $arg)) {
                    list($key, $value) = explode(':', $arg);
                }
                else {
                    self::$args[] = $arg;
                }
            }
        }
        return;
    }

    public static function route($path, $params)
    {
        self::$_routes[$path] = $params;
    }

    private static function _parseURL($url = null)
    {
        if (!$url) {
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        }
        $url = trim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if ($url) {
            return explode('/', $url);
        }
        return array();
    }

    /**
     * Control browser redirects
     *
     * @param $location
     */
    public static function redirect($location)
    {
        header("Location: " . $location);
        exit;
    }

    public static function error404()
    {
        header("HTTP/1.0 404 Not Found");
        $ec = new ErrorController();
        $ec->view->set('title', 'Page Not Found');
        $ec->error404();
    }
}
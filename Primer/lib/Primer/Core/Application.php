<?php
/**
 * Class Bootstrap
 */

namespace Primer\Core;

use ArrayAccess;
use Primer\Component\AuthComponent;
use Primer\Component\SessionComponent;
use Primer\Routing\Router;
use Primer\Proxy\Proxy;
use Primer\View\Form;
use Primer\View\View;
use Primer\Model\Model;

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!defined('PRIMER_CORE')) {
    define('PRIMER_CORE', dirname(dirname(__FILE__)));
}

define('MODELS_PATH', APP_ROOT . DS . 'Models' . DS);
define('CONTROLLERS_PATH', APP_ROOT . DS . 'Controllers' . DS);

require_once(PRIMER_CORE . DS . 'lib/Primer/Utility' . DS . 'PasswordCompatibilityLibrary.php');

class Application implements ArrayAccess
{
    public $request;

    private $_router;
    private $_controller = null;
    private $_view = null;
    private $_action = null;

    private $_bindings = array();
    private $_aliases = array();

    /*
     * Contains values that may be accessible throughout the framework
     */
    private $_values = array();

    /*
     * Contains values to be passed and used in JavaScript through RequireJS
     */
    private $_jsValues = array();

    /**
     * Starts the bootstrap
     */
    public function __construct()
    {
        spl_autoload_register(array(__NAMESPACE__ . '\\Application', 'loadClass'));
        $this->_bootstrap();
    }

    private function _bootstrap()
    {
        // Composer autoloader
        if (file_exists(APP_ROOT . DS . 'vendor/autoload.php')) {
            require_once(APP_ROOT . DS . 'vendor/autoload.php');
        }

        $this->_session = new SessionComponent();
        $this->_auth = new AuthComponent($this->_session);
        $this->_router = new Router();
        $this->instance('\\Primer\\Core\\Application', $this);
        $this->instance('\\Primer\\Components\\SessionComponent', $this->_session);
        $this->instance('\\Primer\\Components\\AuthComponent', $this->_auth);
        $this->instance('\\Primer\\Routing\\Router', $this->_router);

        // Set up dependency injections
        $this->singleton('\\Primer\\Component\\RequestComponent', function () {
            return new \Primer\Component\RequestComponent();
        });

        $aliases = array(
            'app'       => '\\Primer\\Core\\Application',
            'primer'    => '\\Primer\\Core\\Application',
            'router'    => '\\Primer\\Routing\\Router',
            'ioc'       => '\\Primer\\IOC\\IOC',
            'inflector' => '\\Primer\\Utility\\Inflector',
            'session'   => '\\Primer\\Component\\SessionComponent',
            'auth'      => '\\Primer\\Component\\AuthComponent',
            'request'   => '\\Primer\\Component\\RequestComponent',
        );
        foreach ($aliases as $alias => $class) {
            $this->alias($alias, $class);
        }
        $this->_registerProxies();
    }

    private function _registerProxies()
    {
        Proxy::setIOC($this);

        $aliases = array(
            'Primer'  => '\\Primer\\Proxy\\Primer',
            'Session' => '\\Primer\\Proxy\\Session',
            'Auth'    => '\\Primer\\Proxy\\Auth',
            'Request' => '\\Primer\\Proxy\\Request',
            'Router'  => '\\Primer\\Proxy\\Router',
            'IOC'     => '\\Primer\\Proxy\\IOC',
        );

        foreach ($aliases as $alias => $class) {
            Proxy::register($class, $alias);
            $this->alias($alias, $class);
        }
    }

    public function run()
    {
        require_once(APP_ROOT . '/Config/routes.php');

        $this->_router->dispatch();

        $this->setValue('conroller', $this->_router->getController());
        $this->setValue('action', $this->_router->getAction());

        Model::init();
        $this->_view = new View($this->_session, new Form($this->_controller, $this->_action, $this->make('\\Primer\\Component\\RequestComponent')));

        /*
         * Check if chosen controller exists, otherwise, 404
         *
         * We don't want to call Primer::getControllerName here because we don't
         * want /pages/index and /page/index to both work. That function will
         * properly pluralize and format regardless if that controller exists.
         */
        if (file_exists(CONTROLLERS_PATH . ucfirst(strtolower($this->_router->getController())) . 'Controller.php')) {
            $controllerName = Primer::getControllerName($this->_router->getController());
            $this->_controller = new $controllerName($this->_view);
            $this->_callControllerMethod();
        }
        else {
            $this->abort();
        }
    }

    public function loadClass($class)
    {
        if (isset($this->_aliases[$class])) {
            return class_alias($this->_aliases[$class], $class);
        }

        if (isset($this->_bindings[$class])) {
            return $this->make($class);
        }

        $className = ltrim($class, '\\');
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        $path = PRIMER_CORE . '/lib/' . $fileName;

        if (file_exists($path)) {
            require $path;
            return;
        }

        // Load controllers
        if (preg_match('#.+Controller$#', $class)) {
            require_once(CONTROLLERS_PATH . DS . $class . '.php');
            return;
        }

        // Attempt to load in Model files
        $dir = scandir(MODELS_PATH);
        if (in_array($class . '.php', $dir)) {
            require_once(MODELS_PATH . $class . '.php');
            return;
        }
    }

    /**
     * If a method is passed in the GET url parameter
     *
     * http://localhost/controller/method/(param)/(param)/(param)
     * url[0] = Controller
     * url[1] = Method
     * url[...] = Params
     */
    private function _callControllerMethod()
    {
        if (!method_exists($this->_controller, $this->_router->getAction())) {
            $this->abort(404);
        }
        else if ($this->_router->getAction()[0] === '_') {
            $this->_router->error404();
        }

        call_user_func_array(array($this->_controller, 'beforeFilter'), $this->_router->getArgs());

        $authorized = $this->_auth->run($this->_action);
        if (!$authorized) {
            $this->_session->setFlash('You are not authorized to do that', 'notice');
            $referrer = $_SERVER['REQUEST_URI'];
            $this->_router->redirect('/login/?forward_to=' . htmlspecialchars($referrer, ENT_QUOTES, 'utf-8'));
        }

        call_user_func_array(array($this->_controller, $this->_router->getAction()), $this->_router->getArgs());
        call_user_func_array(array($this->_controller, 'afterFilter'), $this->_router->getArgs());
        $this->_controller->view->render($this->_router->getController() . DS . $this->_router->getAction());
    }

    /**
     * Sets a key/value pair in the framework
     *
     * @param string $key name of the key
     * @param mixed $value
     * @param string $category category in which to file the key/value pair; can be a dot-separated path
     */
    public function setValue($key, $value, $category = "default")
    {
        if ($this->_values == null) {
            $this->_values = new \stdClass ();
        }

        $path = explode('.', $category);
        $o = $this->_values;
        foreach ($path as $p) {
            if (!isset ($o->$p)) {
                $o->$p = new \stdClass ();
            }
            $o = $o->$p;
        }

        $o->$key = $value;
    }

    /**
     * Retrieves a key/value pair from the framework
     *
     * @param string $key name of the key
     * @param string $category category in which to file the key/value pair; ; can be a dot-separated path
     *
     * @return mixed value of the key if set, otherwise null
     */
    public function getValue($key, $category = "default")
    {
        if ($this->_values === null) {
            return null;
        }

        $path = explode('.', $category);
        $o = $this->_values;

        foreach ($path as $p) {
            if (!isset ($o->$p)) {
                return null;
            }
            $o = $o->$p;
        }

        if (!isset ($o->$key)) {
            return null;
        }

        return $o->$key;
    }

    /**
     * Deletes a key/value pair from the framework
     *
     * @param string $key name of the key
     * @param string $category category in which to file the key/value pair; ; can be a dot-separated path
     */
    public function deleteValue($key, $category = "default")
    {
        if ($this->_values == null) {
            return;
        }

        $path = explode('.', $category);
        $o = $this->_values;

        foreach ($path as $p) {
            if (!isset ($o->$p)) {
                return;
            }
            $o = $o->$p;
        }

        if (!isset ($o->$key)) {
            return;
        }

        unset ($o->$key);

        return;
    }

    /**
     * Function to set new values to be passed to JavaScript via RequireJS
     *
     * @param $key
     * @param $value
     * @param string $category
     */
    public function setJSValue($key, $value, $category = "default") {
        if ($this->_jsValues == null) {
            $this->_jsValues = new \stdClass();
        }

        $path = explode('.', $category);
        $o = $this->_jsValues;
        foreach ($path as $p) {
            if (!isset($o->$p)) {
                $o->$p = new \stdClass();
            }
            $o = $o->$p;
        }

        $o->$key = $value;
    }

    /**
     * Function to retrieve values passed from PHP to JavaScript via RequireJS
     *
     * @return mixed
     */
    public function getJSValues()
    {
        return $this->_jsValues;
    }

    public function bind($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        if (!is_callable($o)) {
            throw new \RuntimeException(sprintf('Binding is not a valid callable for "%s"', $key));
        }

        $this->_bindings[$key] = $o;
    }

    public function singleton($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        if (!is_callable($o)) {
            throw new \RuntimeException(sprintf('Binding is not a valid callable for "%s"', $key));
        }

        $this->_bindings[$key] = call_user_func($o, $this);
    }

    public function instance($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        $this->_bindings[$key] = $o;
    }

    public function make($key, $parameters = array())
    {
        if (array_key_exists($key, $this->_aliases)) {
            $key = $this->_aliases[$key];
            return $this->make($key);
        }

        if (!array_key_exists($key, $this->_bindings)) {
            $parameters = array();

            $class = new \ReflectionClass($key);
            $method = $class->getMethod('__construct');
            $classParams = $method->getParameters();

            foreach ($classParams as $param) {
                try {
                    $parameters[] = $this->make($param->getClass()->getName());
                } catch (\ReflectionException $e) {
                    echo "Class $key does not exist";
                    return null;
                }
            }

            $newInstance = new \ReflectionClass($key);
            $newInstance = $newInstance->newInstanceArgs($parameters);
            return $newInstance;
        }

        if (is_callable($this->_bindings[$key])) {
            return call_user_func($this->_bindings->get($key), $this, $parameters);
        }

        return $this->_bindings[$key];
    }

    public function alias($alias, $binding)
    {
        $this->_aliases[$alias] = $binding;
    }

    public function offsetExists($key)
    {
        return $this->_aliases[$key];
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->_aliases[$key] = $value;
    }

    public function offsetUnset($key)
    {
        unset($this->_aliases[$key]);
    }

    public function abort($code = 404)
    {
        header("HTTP/1.0 404 Not Found");
        $this->_view->set('title', 'Page Not Found');
        $this->_view->render('error/404');
    }
}

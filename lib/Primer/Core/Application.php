<?php
/**
 * Class Bootstrap
 */

namespace Primer\Core;

use ArrayAccess;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Primer\Console\Console;
use Primer\Http\Response;
use Primer\Security\Auth;
use Primer\Session\Session;
use Primer\Mail\Mail;
use Primer\Model\Model;
use Primer\Proxy\Proxy;
use Primer\Routing\Router;
use Primer\Utility\ParameterContainer;
use Primer\View\Form;
use Primer\View\View;
use Primer\Utility\Paginator;
use Whoops\Exception\ErrorException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Application extends Container
{
    private $_router;
    private $_controller = null;

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
        // checking for minimum PHP version
        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            exit("Sorry, this framework does not run on a PHP version smaller than 5.3.7!");
        }

        spl_autoload_register(
            array(__NAMESPACE__ . '\\Application', 'loadClass')
        );

        $this->_bootstrap();
    }

    private function _bootstrap()
    {
        $this->instance('Primer\\Core\\Application', $this);
        $this->instance('config', new ParameterContainer());

        $this->_readConfigs();
        $this->_registerAliases();
        $this->_registerProxies();
        $this->_registerSingletons();

        $this->bind(
            'Primer\\Mail\\Mail',
            function ($this) {
                return new Mail($this['config']['email']);
            }
        );

        $this->_session = $this->make('Primer\\Session\\Session');
        $this->_auth = $this->make('Primer\\Security\\Auth');
        $this->_router = $this->make('Primer\\Routing\\Router');
    }

    private function _readConfigs()
    {
        $domain = '';
        if (!$this->isRunningInConsole()) {
            $domain = str_replace('www.', '', $_SERVER['SERVER_NAME']);
        }

        if (file_exists(APP_ROOT . '/Config/' . $domain . '.php')) {
            $this['config']['app'] = require_once(APP_ROOT . '/Config/' . $domain . '.php');
        }
        else {
            $this['config']['app'] = require_once(APP_ROOT . '/Config/config.php');
        }

        $this['config']['email'] = require_once(APP_ROOT . DS . 'Config/email.php');
        $this['config']['database'] = require_once(APP_ROOT . DS . 'Config/database.php');

        if ($this['config']['app.debug'] === true) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);

            $whoops = new Run();
            $whoops->pushHandler(new PrettyPageHandler());
            $whoops->register();
        }
        else {
            error_reporting(0);
            ini_set("display_errors", 0);
        }
    }

    /**
     * Alias classes required for use outside of the Primer namespace or to
     * match up with any available proxies.
     */
    private function _registerAliases()
    {
        $aliases = array(
            'app'       => 'Primer\\Core\\Application',
            'primer'    => 'Primer\\Core\\Application',
            'router'    => 'Primer\\Routing\\Router',
            'view'      => 'Primer\\View\\View',
            'ioc'       => 'Primer\\IOC\\IOC',
            'session'   => 'Primer\\Session\\Session',
            'auth'      => 'Primer\\Security\\Auth',
            'request'   => 'Primer\\Http\\Request',
            'mail'      => 'Primer\\Mail\\Mail',
            'security'  => 'Primer\\Utility\\Security',
            'form'      => 'Primer\\View\\Form',
            'response'  => 'Primer\\Http\\Response',
            'cookie'    => 'Primer\\Http\\Cookie',

            /*
             * Third-party aliasing
             */
            'logger'    => 'Monolog\\Logger',
            'Inflector' => 'Primer\\Utility\\Inflector',
            'Carbon'   => 'Carbon\\Carbon',
        );

        foreach ($aliases as $alias => $class) {
            $this->alias($alias, $class);
        }
    }

    /**
     * Run through initial proxy setup and alias necessary classes
     */
    private function _registerProxies()
    {
        Proxy::setApp($this);

        $aliases = array(
            'Config'   => 'Primer\\Proxy\\Config',
            'Primer'   => 'Primer\\Proxy\\Primer',
            'Session'  => 'Primer\\Proxy\\Session',
            'Auth'     => 'Primer\\Proxy\\Auth',
            'View'     => 'Primer\\Proxy\\View',
            'Request'  => 'Primer\\Proxy\\Request',
            'Router'   => 'Primer\\Proxy\\Router',
            'IOC'      => 'Primer\\Proxy\\IOC',
            'Mail'     => 'Primer\\Proxy\\Mail',
            'Security' => 'Primer\\Proxy\\Security',
            'Form'     => 'Primer\\Proxy\\Form',
            'Response' => 'Primer\\Proxy\\Response',
            'Cookie'   => 'Primer\\Proxy\\Cookie',
            'Log'      => 'Primer\\Proxy\\Log',
        );

        foreach ($aliases as $alias => $class) {
            $this->alias($alias, $class);
        }
    }

    private function _registerSingletons()
    {
        $this->singleton('Primer\\Http\\Response');
        $this->singleton('Primer\\Session\\Session');
        $this->singleton('Primer\\Security\\Auth');
        $this->singleton('Primer\\Routing\\Router');
        $this->singleton('Primer\\Http\\Request');
        $this->singleton(
            'Primer\\Datasource\\Database',
            function () {
                try {
                    return new \Primer\Datasource\Database($this['config']['database'][$this['config']->get('app.environment')]);
                } catch (PDOException $e) {
                    die('Database connection could not be established.');
                }
            }
        );
        $this->singleton('Primer\\View\\Form');
        $this->singleton('Primer\\View\\View');
        $this->singleton('Monolog\\Logger', function($app){
            $logger = new Logger('primer');

            $fileName = $app['config']['app.logfile'];
            if ($app['config']['app.log_daily_files'] === true) {
                $logger->pushHandler(new RotatingFileHandler(LOG_PATH . $fileName, 7));
            }
            else {
                $logger->pushHandler(new StreamHandler(LOG_PATH . $fileName));
            }

            return $logger;
        });
    }

    public function run()
    {
        if ($this->isRunningInConsole()) {
            $console = new Console();
            $console->run();
        }
        else {
            $dispatch = $this->_router->dispatch();

            $body = null;
            if (is_array($dispatch)) {
                $this->setValue('conroller', $this->_router->getController());
                $this->setValue('action', $this->_router->getAction());

                Model::init($this->make('Primer\\Datasource\\Database'));

                /*
                 * Check if chosen controller exists, otherwise, 404
                 *
                 * We don't want to call Primer::getControllerName here because we don't
                 * want /pages/index and /page/index to both work. That function will
                 * properly pluralize and format regardless if that controller exists.
                 */
                if (class_exists($this->getControllerName($this->_router->getController()))) {
                    $controllerName = $this->getControllerName(
                        $this->_router->getController()
                    );
                    $this->_controller = new $controllerName();
                    $this['view']->paginator = new Paginator($this->_controller->paginationConfig);
                    $this['view']->paginationConfig = $this->_controller->paginationConfig;
                    $this->_callControllerMethod();

                    if ($this['view']->rendered === false) {
                        $body = $this['view']->render(
                            $this->_router->getController() . DS . $this->_router->getAction()
                        );
                    }
                }
                else {
                    $this->abort();
                }
            }
            else {
                if (is_callable($dispatch)) {
                    $body = call_user_func($dispatch);
                }
            }

            if (!$body) {
                $this->abort();
            }
            else {
                $this['response']->set($body)->send();
            }
        }
    }

    private function isRunningInConsole()
    {
        return php_sapi_name() === 'cli';
    }

    private function isServerDown()
    {
        if (file_exists(APP_ROOT . DS . 'Configs/down')) {
            return true;
        }

        return false;
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
        else {
            if ($this->_router->getAction()[0] === '_') {
                $this->_router->error404();
            }
        }

        call_user_func_array(
            array($this->_controller, 'beforeFilter'),
            $this->_router->getArgs()
        );

        $authorized = $this->_auth->run($this->_router->getAction());
        if (!$authorized) {
            $this->_session->setFlash(
                'You are not authorized to do that',
                'notice'
            );
            $referrer = $_SERVER['REQUEST_URI'];
            $this->_router->redirect(
                '/login/?forward_to=' . htmlspecialchars(
                    $referrer,
                    ENT_QUOTES,
                    'utf-8'
                )
            );
        }

        call_user_func_array(
            array($this->_controller, $this->_router->getAction()),
            $this->_router->getArgs()
        );
        call_user_func_array(
            array($this->_controller, 'afterFilter'),
            $this->_router->getArgs()
        );
    }

    public function abort($code = 404)
    {
        $this['view']->set('title', 'Page Not Found');
        $this['response']->set($this['view']->render('error/404'), $code)->send();
        exit(1);
    }

    public function loadClass($class)
    {
        if (isset($this->_aliases[$class])) {
            return class_alias($this->_aliases[$class], $class);
        }

        if (isset($this->_bindings[$class])) {
            return $this->make($class);
        }

        // Load controllers
        if (preg_match('#.+Controller$#', $class)) {
            if (file_exists($this->getControllersPath() . $class . '.php')) {
                require_once($this->getControllersPath() . $class . '.php');
            }
            return;
        }

        // Attempt to load in Model files
        $dir = scandir($this->getModelsPath());
        if (in_array($class . '.php', $dir)) {
            if (file_exists($this->getModelsPath() . $class . '.php')) {
                require_once($this->getModelsPath() . $class . '.php');
            }
            return;
        }
    }

    public function getControllersPath()
    {
        return APP_ROOT . DS . 'Controllers' . DS;
    }

    public function getModelsPath()
    {
        return APP_ROOT . DS . 'Models' . DS;
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
    public function setJSValue($key, $value, $category = "default")
    {
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
}

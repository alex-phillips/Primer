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
use Primer\Http\Request;
use Primer\Proxy\Proxy;
use Primer\Routing\Dispatcher;
use Primer\Utility\ParameterBag;
use Primer\Utility\Paginator;
use Whoops\Exception\ErrorException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Application extends Container
{
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
            array(
                __NAMESPACE__ . '\\Application',
                'loadClass'
            )
        );

        $this->_bootstrap();
    }

    private function _bootstrap()
    {
        $this->instance('Primer\\Core\\Application', $this);
        $this->instance('config', new ParameterBag());

        $this->_registerAliases();
        $this->_registerProxies();
        $this->_registerSingletons();
        $this->_readConfigs();

        $this->bind(
            'Primer\\Mail\\Mail',
            function ($this) {
                return new Mail($this['config']['email']);
            }
        );
    }

    private function _readConfigs()
    {
        $domain = '';
        if (!$this->isRunningInConsole()) {
            $domain = str_replace('www.', '', $_SERVER['SERVER_NAME']);
        }

        $files = scandir(APP_ROOT . DS . 'Config' . DS);
        foreach ($files as $file) {
            if (preg_match("#$domain.php$#", $file)) {
                $this['config']['app'] = require(APP_ROOT . '/Config/' . $file . '.php');
                continue;
            }

            if (preg_match('#(.+?)\.php$#', $file, $matches)) {
                $this['config'][$matches[1]] = require_once(APP_ROOT . DS . 'Config' . DS . $file);
            }
        }

        $this['config']['database'] = $this['config']['database'][$this['config']['app']['environment']];

        if ($this['config']['app.debug'] === true || $this->isRunningInConsole()) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);

            if (!$this->isRunningInConsole()) {
                $whoops = new Run();
                $whoops->pushHandler(new PrettyPageHandler());
                $whoops->register();
            }
        }
        else {
            error_reporting(0);
            ini_set("display_errors", 0);
        }
    }

    private function isRunningInConsole()
    {
        return php_sapi_name() === 'cli';
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
            'form'      => 'Primer\\Form\\Form',
            'response'  => 'Primer\\Http\\Response',
            'cookie'    => 'Primer\\Http\\Cookie',

            /*
             * Third-party aliasing
             */
            'logger'    => 'Monolog\\Logger',
            'Inflector' => 'Primer\\Utility\\Inflector',
            'Carbon'    => 'Carbon\\Carbon',
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
        $this->singleton('Primer\\Http\\Request', function($app) {
                return new Request($app['router']->matchCurrentRequest());
            });
        $this->singleton(
            'Primer\\Datasource\\Database',
            function () {
                try {
                    \Primer\Datasource\Database::getInstance();
                } catch (PDOException $e) {
                    die('Database connection could not be established.');
                }
            }
        );
        $this->singleton('Monolog\\Logger', function ($app) {
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
        /*
         * If the server is down for maintenance, only instantiate necessary
         * objects to send response.
         */
        if ($this->isServerDown()) {
            $this['view']->set('title', 'Down for Maintenance');
            $this['view']->useTemplate('ajax');
            $this['response']->set($this['view']->render('errors/maintenance'))->send();
            exit(1);
        }

        require_once(APP_ROOT . '/Config/routes.php');

        if ($this->isRunningInConsole()) {
            $console = new Console();
            $console->run();
        }
        else {
            $dispatcher = new Dispatcher($this['request']);
            $this['response']->set($dispatcher->dispatch())->send();
        }
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

    public function abort($code = 404)
    {
        $this['view']->set('title', 'Page Not Found');
        $this['response']->set($this['view']->render('errors/404'), $code)->send();
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

        // Load commands
        if (preg_match('#.+Command$#', $class)) {
            if (file_exists($this->getCommandsPath() . $class . '.php')) {
                require_once($this->getCommandsPath() . $class . '.php');
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

    public function getCommandsPath()
    {
        return APP_ROOT . DS . 'Commands' . DS;
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

    private function isServerDown()
    {
        if (file_exists(APP_ROOT . DS . 'Config/down')) {
            return true;
        }

        return false;
    }
}

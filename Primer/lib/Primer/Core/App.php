<?php
/**
 * Class Bootstrap
 */

namespace Primer\Core;

use Primer\Routing\Router;

use Primer\IOC\DI;

class App
{
    public $request;

    private $_controller = null;

    /**
     * Starts the bootstrap
     */
    public function __construct()
    {
        // @TODO: handle these differently (for global access) - maybe create a facade class or helper?
        Primer::createAlias('\\Primer\\Routing\\Router', 'Router');
        Primer::createAlias('\\Primer\\IOC\\DI', 'DI');
        Primer::createAlias('\\Primer\\Utility\\Inflector', 'Inflector');
        Primer::createAlias('\\Primer\\Core\\Primer', 'Primer');

        // Set up dependency injections
        DI::init();
        DI::singleton('SessionComponent', function() {
            return new \Primer\Component\SessionComponent();
        });
        DI::singleton('AuthComponent', function() {
            return new \Primer\Component\AuthComponent(DI::make('SessionComponent'));
        });
        DI::singleton('RequestComponent', function() {
            return new \Primer\Component\RequestComponent();
        });

        $session = DI::make('SessionComponent');

        if (file_exists(APP_ROOT . DS . 'vendor/autoload.php')) {
            require_once(APP_ROOT . DS . 'vendor/autoload.php');
        }

        require_once(APP_ROOT . '/Config/routes.php');
        Router::dispatch();

        if (defined('UNDER_CONSTRUCTION') && UNDER_CONSTRUCTION === true) {
            if (!$session->isUserLoggedIn()) {
                if (Router::$controller !== 'users') {
                    echo '<h1>Under Construction</h1>';
                    exit;
                }
                if (Router::$action !== 'login') {
                    echo '<h1>Under Construction</h1>';
                    exit;
                }
            }
        }

        Primer::setValue('conroller', Router::$controller);
        Primer::setValue('action', Router::$action);

        /*
         * Check if chosen controller exists, otherwise, 404
         *
         * We don't want to call Primer::getControllerName here because we don't
         * want /pages/index and /page/index to both work. That function will
         * properly pluralize and format regardless if that controller exists.
         */
        if (file_exists(CONTROLLERS_PATH . ucfirst(strtolower(Router::$controller)) . 'Controller.php')) {
            $this->_loadController(Router::$controller);
            $this->_callControllerMethod();
        }
        else {
            Router::error404();
        }
    }

    /**
     * Load necessary controller
     *
     * @param $controller
     */
    private function _loadController($controller)
    {
        $controllerName = Primer::getControllerName($controller);
        $this->_controller = DI::make($controllerName);
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
        if (!method_exists($this->_controller, Router::$action)) {
            Router::error404();
        }
        else if (Router::$action[0] === '_') {
            Router::error404();
        }
        call_user_func_array(array($this->_controller, 'beforeFilter'), Router::$args);
        call_user_func_array(array($this->_controller, Router::$action), Router::$args);
        call_user_func_array(array($this->_controller, 'afterFilter'), Router::$args);
        $this->_controller->view->render(Router::$controller . DS . Router::$action);
    }
}

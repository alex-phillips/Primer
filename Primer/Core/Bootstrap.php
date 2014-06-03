<?php
/**
 * Class Bootstrap
 */

class Bootstrap
{
    public $request;

    private $_controller = null;

    /**
     * Starts the bootstrap
     */
    public function __construct()
    {
        Router::dispatch();

        if (defined('UNDER_CONSTRUCTION') && UNDER_CONSTRUCTION === true) {
            $session = SessionComponent::getInstance();
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
        // Check if chosen controller exists, otherwise, 404
        if (file_exists(CONTROLLERS_PATH . Primer::getControllerName(Router::$controller) . '.php')) {
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
        $this->_controller = new $controllerName;
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
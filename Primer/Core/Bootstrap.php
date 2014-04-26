<?php
/**
 * Class Bootstrap
 */

class Bootstrap
{
    public $request;

    private $_url = array();
    private $_controller = null;

    /**
     * Starts the bootstrap
     */
    public function __construct()
    {
        /* Initialize main components */
        Session::init();
        Auth::init();
        $this->request = Request::getInstance();

        require_once(APP_ROOT . '/Config/routes.php');
        Router::dispatch();

        Primer::setValue('conroller', Router::$controller);
        Primer::setValue('action', Router::$action);
        // Check if chosen controller exists, otherwise, 404
        if (file_exists(APP_ROOT . '/Controllers/' . ucfirst(Router::$controller) . '_Controller.php')) {
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
        $controllerName = $controller . '_Controller';
        $this->_controller = new $controllerName;
        $this->_controller->loadModel();
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
    }
}
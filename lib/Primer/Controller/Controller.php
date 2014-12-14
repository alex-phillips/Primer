<?php

namespace Primer\Controller;

use Primer\Http\Request;
use Primer\Utility\Inflector;

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    /////////////////////////////////////////////////
    // PROPERTIES, PUBLIC
    /////////////////////////////////////////////////

    public $components = array();

    public $request;

    public $view;

    public $layout = 'default';

    public $viewClass = 'Primer\\View\\View';

    public $viewVars = array();

    /**
     * Default pagination settings
     *
     * @var array
     */
    public $paginationConfig = array(
            'perPage'  => 10,
            'instance' => 'p',
            'query'    => 'q',
        );

    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    /**
     * Variable of the model name of the controller
     * i.e. 'User' model for UsersController
     *
     * @var string
     */
    protected $_modelName;

    public function __construct()
    {
        $this->_modelName = ucfirst(
            strtolower(
                Inflector::singularize(
                    str_replace('Controller', '', get_class($this))
                )
            )
        );

        // Load Model (if controller's model exists)
        $this->loadModel();
    }

    public function __set($key, $value)
    {
        if ($key === 'components') {
            array_merge($this->$key, $value);
        }
        else {
            $this->$key = $value;
        }
    }

    public function set($one, $two = null)
    {
        if (is_array($one)) {
            if (is_array($two)) {
                $data = array_combine($one, $two);
            }
            else {
                $data = $one;
            }
        }
        else {
            $data = array($one => $two);
        }

        $this->viewVars = $data + $this->viewVars;
    }

    public function invokeAction(Request $request)
    {
        try {
            $this->setRequest($request);
            $method = new \ReflectionMethod($this, $request->params['action']);

            if (!$this->_isPrivateAction($method)) {
                // error out
            }

            $method->invokeArgs($this, $request->params['pass']);

            return $this->render();
        }
        catch (\ReflectionException $e) {
            $test = 1;
        }
    }

    protected function _isPrivateAction(\ReflectionMethod $method)
    {
        if ($method->name[0] === '_' || !$method->isPublic()) {
            return true;
        }

        return false;
    }

    /**
     * Loads the controllers associated model
     */
    public function loadModel()
    {
        if (class_exists($this->_modelName)) {
            $this->{$this->_modelName} = call_user_func(array($this->_modelName, 'create'));
        }
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        $this->view = array();
        if (isset($request->params['controller'])) {
            $this->view[] = $request->params['controller'];
        }
        if (isset($request->params['action'])) {
            $this->view[] = $request->params['action'];
        }

        $this->view = implode('.', $this->view);
    }

    protected function render($view = null, $template = null)
    {
        $viewObject = $this->_getViewObject();
        $view = $view ?: $this->view;

        return $viewObject->render($view, $template);
    }

    protected function _getViewObject()
    {
        $viewClass = $this->viewClass;

        return new $viewClass($this);
    }

    public function beforeFilter()
    {
        return true;
    }

    public function afterFilter()
    {
        return true;
    }
}
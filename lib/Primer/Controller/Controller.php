<?php

namespace Primer\Controller;

use Primer\Http\Request;
use Primer\Http\Response;
use Primer\Utility\Inflector;
use Primer\Controller\Exception\MissingActionException;

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

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        // @TODO: Clean this up - we should instantiate a default model.
        $modelName = ucfirst(
            strtolower(
                Inflector::singularize(
                    str_replace('Controller', '', get_class($this))
                )
            )
        );

        if (class_exists($modelName)) {
            $this->{$modelName} = new $modelName;
        }
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
            throw new MissingActionException($request->params['action']);
        }
    }

    protected function _isPrivateAction(\ReflectionMethod $method)
    {
        if ($method->name[0] === '_' || !$method->isPublic()) {
            return true;
        }

        return false;
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

    public function render($view = null, $template = 'default')
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
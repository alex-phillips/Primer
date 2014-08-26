<?php

namespace Primer\Controller;

use Primer\Model\Model;
use Primer\Utility\Inflector;
use Primer\Utility\Paginator;
use Primer\View\View;

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    /*
     * Variable of the model name of the controller
     * i.e. 'User' model for UsersController
     */
    protected $_modelName;

    /*
     * Array of components to be loaded into controller, model, and view
     */
    protected $_components = array();

    /*
     * Default pagination settings
     */
    protected $_paginationConfig = array(
        'perPage' => 10,
        'instance' => 'p'
    );

    public function __construct(View $view)
    {
        $this->view = $view;
        $this->view->paginator = new Paginator($this->_paginationConfig);
        $this->_modelName = ucfirst(strtolower(Inflector::singularize(str_replace('Controller', '', get_class($this)))));

        // Load Model (if controller's model exists)
        $this->loadModel();
    }

    /**
     * Loads the controllers associated model
     */
    public function loadModel()
    {
        if (class_exists($this->_modelName)) {
            $this->{$this->_modelName} = new $this->_modelName();
        }
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
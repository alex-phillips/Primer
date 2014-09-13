<?php

namespace Primer\Controller;

use Primer\Utility\Inflector;
use Primer\Utility\Paginator;

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    /*
     * Default pagination settings
     */
    public $paginationConfig = array(
        'perPage' => 10,
        'instance' => 'p'
    );

    /////////////////////////////////////////////////
    // PROPERTIES, PRIVATE AND PROTECTED
    /////////////////////////////////////////////////

    /*
     * Variable of the model name of the controller
     * i.e. 'User' model for UsersController
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
<?php

namespace Primer\Controller;

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

    /**
     * Loads the controllers associated model
     */
    public function loadModel()
    {
        if (class_exists($this->_modelName)) {
            $this->{$this->_modelName} = call_user_func(array($this->_modelName, 'create'));
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
<?php

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

    public function __construct()
    {
        $this->_modelName = ucfirst(strtolower(Inflector::singularize(str_replace('Controller', '', get_class($this)))));

        // Load Model (if controller's model exists)
        $this->loadModel();
        // Load View
        $this->view = new View();

        // @TODO need a better way to give View the same components as Controller. Helpers?
        foreach ($this->_components as $component) {
            if (file_exists(PRIMER_CORE . DS . 'Components' . DS . $component . 'Component.php')) {
                Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . $component . 'Component.php');
                $this->$component = call_user_func(array($component . 'Component', 'getInstance'));
                $this->view->$component = $this->$component;
            }
        }
        Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . 'RequestComponent.php');
        $this->request = RequestComponent::getInstance();
        $this->view->request = $this->request;

        $this->view->paginator = new Paginator($this->_paginationConfig);
    }

    /**
     * Loads the controllers associated model
     */
    public function loadModel()
    {
        $path = MODELS_PATH . $this->_modelName . '.php';
        // @TODO: Find better way to handle in model doesn't exist
        if (file_exists($path)) {
            Primer::requireFile($path);
            $this->{$this->_modelName} = new $this->_modelName();
        }
        else {
            Model::init();
        }

        // @TODO: do we need to handle situation where model doesn't exist for controller? i.e. pages
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
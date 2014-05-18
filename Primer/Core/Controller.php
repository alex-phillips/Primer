<?php

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    protected $_modelName;
    protected $_modelLoaded = false;
    public $components = array();
    public $pagination_config = array(
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
        foreach ($this->components as $component) {
            if (file_exists(PRIMER_CORE . DS . 'Components' . DS . $component . '.php')) {
                Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . $component . '.php');
                $this->$component = $component::getInstance();
                $this->view->$component = $this->$component;
                if ($this->_modelLoaded) {
                    $this->{$this->_modelName}->$component = $this->$component;
                }
            }
        }
        Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . 'Request.php');
        $this->request = Request::getInstance();
        $this->view->request = $this->request;

        $this->view->paginator = new Paginator($this->pagination_config);
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
            $this->_modelLoaded = true;
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
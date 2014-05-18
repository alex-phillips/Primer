<?php

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    protected $_modelName;
    public $components = array();
    public $pagination_config = array(
        'perPage' => 10,
        'instance' => 'p'
    );

    public function __construct()
    {
        $this->_modelName = strtolower(Inflector::singularize(str_replace('Controller', '', get_class($this))));

        $this->view = new View();

        // @TODO need a better way to give View the same components as Controller
        foreach ($this->components as $component) {
            if (file_exists(PRIMER_CORE . DS . 'Components' . DS . $component . '.php')) {
                Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . $component . '.php');
                $this->$component = $component::getInstance();
                $this->view->$component = $this->$component;
            }
        }
        Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . 'Request.php');
        $this->request = Request::getInstance();
        $this->view->request = $this->request;

        $this->view->paginator = new Paginator($this->pagination_config);
        $this->loadModel();
    }

    /**
     * Loads the controllers associated model
     */
    public function loadModel()
    {
        $path = MODELS_PATH . ucfirst($this->_modelName) . '.php';
        // @TODO: Find better way to handle in model doesn't exist
        if (file_exists($path)) {
            Primer::requireFile($path);
            $this->{ucfirst($this->_modelName)} = new $this->_modelName();
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
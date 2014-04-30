<?php

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    public $request;
    public $pagination_config = array(
        'perPage' => 10,
        'instance' => 'p'
    );

    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->view = new View();

        $this->view->paginator = new Paginator($this->pagination_config);
    }

    /**
     * Loads the controllers associated model
     */
    public function loadModel()
    {
        $path = MODELS_PATH . ucfirst($this->name) . '.php';
        // @TODO: Find better way to handle in model doesn't exist
        if (file_exists($path)) {
            Primer::requireFile($path);

            $modelName = ucfirst($this->name);
            $this->{$modelName} = new $modelName();
        }

        // @TODO: do we need to handle situation where model doesn't exist for controller? i.e. pages
    }

    public function beforeFilter()
    {
        return true;
    }

    /**
     * Control browser redirects
     *
     * @param $location
     */
    public function redirect($location)
    {
        header("Location: " . $location);
        exit;
    }
}
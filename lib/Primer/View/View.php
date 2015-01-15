<?php

namespace Primer\View;

use Primer\Controller\Controller;
use Primer\Core\Object;
use Primer\Utility\Paginator;
use Primer\View\Exception\MissingViewException;
use Whoops\Example\Exception;

/**
 * Class View
 */
class View extends Object
{
    public $paginationConfig = array();

    /**
     * This variable holds the filename of the view to be rendered inside the
     * template
     *
     * @var
     */
    public $filename;

    /**
     * Title that is used in the HTML tag. Also accessible in views and templates
     *
     * @var string
     */
    public $title = '';

    /**
     * Variable that holds the paginator object to build pagination as well as
     * the paging links
     *
     * @var
     */
    public $paginator;

    /**
     * This determines the template file that is to be used
     *
     * @var string
     */
    public $template = 'default';

    public $viewVars = array();

    public $rendered = false;

    /**
     * Additional CSS files to be included at render
     *
     * @var string
     */
    private static $_cssFiles = array();

    /**
     * Additional JS files to be included at render
     *
     * @var array
     */
    private static $_jsFiles = array();

    /**
     * Constructor
     */
    public function __construct(Controller $controller = null)
    {
        if ($controller) {
            $this->paginationConfig = $controller->paginationConfig;
            $this->viewVars = $controller->viewVars;
        }

        $this->paginator = new Paginator($this->paginationConfig);
    }

    /**
     * Adds a CSS requirement to the list of all required CSS files
     *
     * @param string $require_css_file the full URL to the CSS file
     */
    public static function addCSS($require_css_file)
    {
        if (self::$_cssFiles == null) {
            self::$_cssFiles = array();
        }

        // don't add things twice
        if (in_array($require_css_file, self::$_cssFiles)) {
            return;
        }
        self::$_cssFiles[] = $require_css_file;
    }

    /**
     * Gets the array of all required CSS files
     *
     * @return array
     */
    public static function getCSS()
    {
        if (self::$_cssFiles == null) {
            self::$_cssFiles = array();
        }
        return self::$_cssFiles;
    }

    /**
     * Used to add additional JS files
     *
     * @param $file
     */
    public static function addJS($file)
    {
        if (in_array($file, self::$_jsFiles)) {
            return;
        }
        self::$_jsFiles[] = $file;
    }

    /**
     * Gets the array of all required javascript modules
     *
     * @return array
     */
    public static function getJS()
    {
        if (self::$_jsFiles == null) {
            self::$_jsFiles = array();
        }
        return self::$_jsFiles;
    }

    public function make($view)
    {
        $this->filename = $view;
    }

    public function useTemplate($template)
    {
        $this->template = str_replace('.', '/', $template);
    }

    /**
     * Render the view given a template (or using the default template)
     * and using the passed in file to render the contents of the page.
     *
     * @param $view
     *
     * @return mixed|string
     */
    public function render($view, $template = null)
    {
        if (ob_get_contents()) {
            ob_clean();
        }
        ob_start();

        $view = str_replace('.', '/', $view);
        $this->filename = APP_ROOT . '/Views/' . $view . '.php';

        extract($this->viewVars);
        if ($template) {
            if (!file_exists(APP_ROOT . "/Views/templates/$this->template.php")) {
                throw new MissingViewException($this->template);
            }
            require(APP_ROOT . "/Views/templates/$this->template.php");
        }
        else {
            if (!file_exists($this->filename)) {
                throw new MissingViewException($this->filename);
            }
            require($this->filename);
        }

        if ($response = ob_get_contents()) {
            ob_end_clean();
        }

        $this->rendered = true;

        return $response;
    }

    public function fetch($partial, $default = '')
    {
        if ($partial === 'content') {
            $partial = $this->filename;
        }
        else {
            if (!preg_match('#\.php$#', $partial)) {
                $partial .= '.php';
            }
            $partial = APP_ROOT . '/Views/' . $partial;
        }

        try {
            extract($this->viewVars);
            if (!file_exists($partial)) {
                throw new MissingViewException($partial);
            }
            require_once($partial);
        }
        catch (Exception $e) {
            echo $default;
        }
    }

    public function build($partial, $default = '')
    {
        if (!preg_match('#\.php$#', $partial)) {
            $partial .= '.php';
        }

        if (preg_match('#([^\/]*?)\.php$#', $partial, $matches)) {
            try {
                require_once(APP_ROOT . '/Views/' . $partial);
                $className = $matches[1];
                $partial = new \ReflectionClass($className);
                $renderer = $partial->getMethod('render');
                return $renderer->invokeArgs($partial, $this->viewVars);
            }
            catch (Exception $e) {
                return $default;
            }
        }
    }

    /**
     * Function for use in controller to set variables to be used in the view
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->$k = $v;
            }
        }
        else {
            $this->$key = $value;
        }
    }

    public function paginate($modelName, $conditions = array())
    {
        $this->paginator->set_total(
            call_user_func(
                array(
                    $modelName,
                    'findCount'
                ),
                array('conditions' => $conditions)
            )
        );

        $params = array(
            'conditions' => $conditions,
            'limit' => $this->paginator->get_limit(),
        );

        if (isset($this->paginationConfig['order'])) {
            $params['order'] = $this->paginationConfig['order'];
        }

        return call_user_func(
            array(
                $modelName,
                'find'
            ),
            $params
        );
    }
}
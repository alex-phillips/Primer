<?php

namespace Primer\View;

use Primer\Session\Session;
use Primer\Core\Object;
use Primer\Routing\Router;

/**
 * Class View
 */
class View extends Object
{
    /*
     * Additional CSS files to be included at render
     */
    private static $_cssFiles = '';

    /*
     * Additional JS files to be included at render
     */
    private static $_jsFiles = array();

    public $paginationConfig = array();

    /*
     * This variable holds the filename of the view to be rendered inside the
     * template
     */
    public $filename;

    /*
     * Title that is used in the HTML tag. Also accessible in views and templates
     */
    public $title = '';

    /*
     * Variable that holds the paginator object to build pagination as well as
     * the paging links
     */
    public $paginator;

    /*
     * This determines the template file that is to be used
     */
    public $template = 'default';

    /**
     * Constructor
     */
    public function __construct(Session $session)
    {
        $this->Session = $session;
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

    /**
     * Render the view given a template (or using the default template)
     * and using the passed in file to render the contents of the page.
     *
     * @param $view
     *
     * @return mixed|string
     */
    public function render($view)
    {
        // @TODO: modify code to use a proper reponse class
        ob_clean();
        ob_start();
        $view = str_replace('.', '/', $view);
        $this->filename = 'Views/' . $view . '.php';

        $response = '';
        if (isset($this->request->format)) {
            switch ($this->request->format) {
                case 'json':
                    echo app()->getValue('rendering_object')->JSONSerialize();
                default:
                    Router::error404();
                    break;
            }
        }
        else {
            require_once("Views/templates/$this->template.php");
        }

        $this->Session->delete('messages');

        $response = ob_get_contents();
        ob_end_clean();

        return $response;
    }

    /**
     * Function to format and return system messages to display in the view
     *
     * @return string
     */
    public function flash()
    {
        $markup = '';
        if ($this->Session->read('flash_messages')) {
            foreach ($this->Session->read(
                'flash_messages'
            ) as $error => $class) {
                $markup .= "<div class='system-message $class'>" . $error . "</div>";
            }
        }

        $this->Session->delete('flash_messages');
        return $markup;
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
        return call_user_func(
            array(
                $modelName,
                'find'
            ),
            array(
                'conditions' => $conditions,
                'limit'      => $this->paginator->get_limit(),
                'order'      => $this->paginationConfig['order'],
            )
        );
    }

    /**
     * This function requires the correct view to be rendered inside of the
     * template
     */
    protected function getContents()
    {
        require_once("{$this->filename}");
    }
}
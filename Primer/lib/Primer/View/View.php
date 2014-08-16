<?php

namespace Primer\View;

use Primer\Routing\Router;
use Primer\Core\Primer;

/**
 * Class View
 */
class View
{
    /*
     * Additional CSS files to be included at render
     */
    private static $_cssFiles = '';

    /*
     * Additional JS files to be included at render
     */
    private static $_jsFiles = array();

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
     * Variable that holds the Form object to build forms inside of views
     */
    public $Form;

    /*
     * This determines the template file that is to be used
     */
    public $template = 'default';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->title = Primer::getValue('action');
        $this->Form = new Form(Router::$controller, Primer::getValue('action'));
    }

    /**
     * Render the view given a template (or using the default template)
     * and using the passed in file to render the contents of the page.
     *
     * @param $filename
     */
    public function render($filename)
    {
        $this->filename = 'Views/' . $filename . '.php';

        if (isset($this->request->format)) {
            switch ($this->request->format) {
                case 'json':
                    echo Primer::getValue('rendering_object')->JSONSerialize();
                    break;
                default:
                    Router::error404();
                    break;
            }
            exit;
        }
        else {
            require_once("Views/templates/$this->template.php");
        }

        $this->Session->delete('messages');
        exit(1);
    }

    /**
     * This function requires the correct view to be rendered inside of the
     * template
     */
    protected function getContents ()
    {
        require_once("{$this->filename}");
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
            foreach ($this->Session->read('flash_messages') as $error => $class) {
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
    public function set($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * Adds a CSS requirement to the list of all required CSS files
     * @param string $require_css_file the full URL to the CSS file
     */
    public static function addCSS ($require_css_file)
    {
        if (self::$_cssFiles == null)
        {
            self::$_cssFiles = array();
        }

        // don't add things twice
        if (in_array ($require_css_file, self::$_cssFiles))
        {
            return;
        }
        self::$_cssFiles[] = $require_css_file;
    }

    /**
     * Gets the array of all required CSS files
     *
     * @return array
     */
    public static function getCSS ()
    {
        if (self::$_cssFiles == null)
        {
            self::$_cssFiles = array();
        }
        return self::$_cssFiles;
    }

    /**
     * Used to add additional JS files
     *
     * @param $file
     */
    public static function addJS($file) {
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
    public static function getJS ()
    {
        if (self::$_jsFiles == null)
        {
            self::$_jsFiles = array();
        }
        return self::$_jsFiles;
    }
}
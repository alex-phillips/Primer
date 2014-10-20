<?php

namespace Primer\View;

use Primer\Session\Session;
use Primer\Core\Object;

/**
 * Class View
 */
class View extends Object
{
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

    public $rendered = false;

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
    public function render($view)
    {
        // @TODO: modify code to use a proper reponse class
        if (ob_get_contents()) {
            ob_clean();
        }
        ob_start();

        if ($this->filename) {
            $view = $this->filename;
        }

        $view = str_replace('.', '/', $view);
        $this->filename = 'Views/' . $view . '.php';

        require_once("Views/templates/$this->template.php");

        $this->Session->delete('messages');

        $response = '';
        if ($response = ob_get_contents()) {
            ob_end_clean();
        }

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

    /**
     * This function requires the correct view to be rendered inside of the
     * template
     */
    protected function getContents()
    {
        require_once("{$this->filename}");
    }
}
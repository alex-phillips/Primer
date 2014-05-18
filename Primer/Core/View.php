<?php

require_once(PRIMER_CORE . '/lib/Paginator.php');

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


    public $filename;
    public $title = '';
    public $paginator;
    public $Form;
    public $template = 'default';

    public function __construct() {
        $this->title = Primer::getValue('action');
    }

    /**
     * Render the view given a template (or using the default template)
     * and using the passed in file to render the contents of the page.
     *
     * @param $filename
     */
    public function render($filename)
    {
        list($controller) = explode('_', strtolower(Primer::getValue('controller')), 1);
        $this->Form = new Form($controller, Primer::getValue('action'));

        $this->filename = 'Views/' . $filename . '.php';

        if (isset($this->request->format)) {
            switch ($this->request->format) {
                case 'json':
                    echo json_encode(Primer::getValue('rendering_object'));
                    break;
                default:
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

    public function getContents ()
    {
        require_once("{$this->filename}");
    }

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
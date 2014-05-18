<?php
/**
 * @author Alex Phillips
 * Date: 3/18/14
 * Time: 12:26 PM
 */

// dev error reporting
error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!defined('PRIMER_CORE')) {
    define('PRIMER_CORE', dirname(dirname(__FILE__)));
}

define('MODELS_PATH', APP_ROOT . DS . 'Models' . DS);
define('CONTROLLERS_PATH', APP_ROOT . DS . 'Controllers' . DS);

Primer::requireFile(PRIMER_CORE . '/lib/PasswordCompatibilityLibrary.php');
spl_autoload_register('Primer::autoload');

/**
 * the autoloading function, which will be called every time a file "is missing"
 * NOTE: don't get confused, this is not "__autoload", the now deprecated function
 * The PHP Framework Interoperability Group (@see https://github.com/php-fig/fig-standards) recommends using a
 * standardized autoloader https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md, so we do:
 *
 * @param $class
 */

class Primer
{
    private static $_jsValues;
    private static $_values = array();
    private static $_loadedFiles = array();

    public static function autoload($class)
    {
        // First attempt libs directory, then attempt Objects in libs
        $dir = scandir(PRIMER_CORE . '/lib');
        foreach ($dir as $file) {
            if (strtolower($file) == strtolower($class . '.php')) {
                Primer::requireFile(PRIMER_CORE . "/lib/" . $file);
                return;
            }
        }

        $dir = scandir(PRIMER_CORE . '/Core');
        foreach ($dir as $file) {
            if (strtolower($file) == strtolower($class . '.php')) {
                Primer::requireFile(PRIMER_CORE . "/Core/" . $file);
                return;
            }
        }

        $dir = scandir(MODELS_PATH);
        foreach ($dir as $file) {
            if (strtolower($file) == strtolower($class . '.php')) {
                Primer::requireFile(MODELS_PATH . $file);
                return;
            }
        }

        $dir = scandir(CONTROLLERS_PATH);
        foreach ($dir as $file) {
            if (strtolower($file) == strtolower($class . '.php')) {
                Primer::requireFile(CONTROLLERS_PATH . $file);
                return;
            }
        }
    }

    public static function requireFile($filename)
    {
        if (!in_array($filename, self::$_loadedFiles)) {
            require_once("$filename");
        }
    }

    public static function setJSValue($key, $value, $category = "default") {
        if (self::$_jsValues == null) {
            self::$_jsValues = new stdClass();
        }

        $path = explode('.', $category);
        $o = self::$_jsValues;
        foreach ($path as $p) {
            if (!isset($o->$p)) {
                $o->$p = new stdClass();
            }
            $o = $o->$p;
        }

        $o->$key = $value;
    }

    public static function getJSValues()
    {
        return self::$_jsValues;
    }

    /**
     * Sets a key/value pair in the framework
     *
     * @param string $key name of the key
     * @param mixed $value
     * @param string $category category in which to file the key/value pair; can be a dot-separated path
     */
    public static function setValue ($key, $value, $category = "default")
    {
        if (self::$_values == null)
        {
            self::$_values = new \stdClass ();
        }

        $path = explode ('.', $category);
        $o = self::$_values;
        foreach ($path as $p)
        {
            if (!isset ($o->$p))
            {
                $o->$p = new \stdClass ();
            }
            $o = $o->$p;
        }

        $o->$key = $value;
    }

    /**
     * Retrieves a key/value pair from the framework
     *
     * @param string $key name of the key
     * @param string $category category in which to file the key/value pair; ; can be a dot-separated path
     * @return mixed value of the key if set, otherwise null
     */
    public static function getValue ($key, $category = "default")
    {
        if (self::$_values == null) {
            return null;
        }

        $path = explode ('.', $category);
        $o = self::$_values;

        foreach ($path as $p) {
            if (!isset ($o->$p)) {
                return null;
            }
            $o = $o->$p;
        }

        if (!isset ($o->$key)) {
            return null;
        }

        return $o->$key;
    }

    /**
     * Deletes a key/value pair from the framework
     *
     * @param string $key name of the key
     * @param string $category category in which to file the key/value pair; ; can be a dot-separated path
     */
    public static function deleteValue ($key, $category = "default")
    {
        if (self::$_values == null) {
            return;
        }

        $path = explode ('.', $category);
        $o = self::$_values;

        foreach ($path as $p) {
            if (!isset ($o->$p)) {
                return;
            }
            $o = $o->$p;
        }

        if (!isset ($o->$key)) {
            return;
        }

        unset ($o->$key);

        return;
    }

    public static function logMessage ($msg, $filename = 'default')
    {
        $pid = getmypid();
        $dt = date("Y-m-d H:i:s (T)");
        $fullpath = LOG_PATH . $filename;
        error_log("$dt\t$pid\t$msg\n", 3, $fullpath);
    }

    public static function getControllerName($string)
    {
        return ucfirst(Inflector::pluralize($string) . 'Controller');
    }
}
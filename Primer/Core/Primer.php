<?php
/**
 * @author Alex Phillips
 * Date: 3/18/14
 * Time: 12:26 PM
 */

namespace Primer\Core;

use Primer\lib\Inflector;
use Primer\Components\RequestComponent;

// dev error reporting
error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!defined('PRIMER_CORE')) {
    define('PRIMER_CORE', dirname(dirname(__FILE__)));
}

define('MODELS_PATH', APP_ROOT . DS . 'Models' . DS);
define('CONTROLLERS_PATH', APP_ROOT . DS . 'Controllers' . DS);

Primer::requireFile(PRIMER_CORE . DS . 'lib' . DS . 'PasswordCompatibilityLibrary.php');
spl_autoload_register(__NAMESPACE__ . '\\Primer::autoload');

/**
 * the autoloading function, which will be called every time a file "is missing"
 * NOTE: don't get confused, this is not "__autoload", the now deprecated function
 * The PHP Framework Interoperability Group (@see https://github.com/php-fig/fig-standards) recommends using a
 * standardized autoloader https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md, so we do:
 *
 * @param $class
 */

/**
 * Class Primer
 */
class Primer
{
    /*
     * Contains values to be passed and used in JavaScript through RequireJS
     */
    private static $_jsValues;

    /*
     * Contains values that may be accessible throughout the framework
     */
    private static $_values = array();

    /*
     * Contains files that have already been loaded
     */
    private static $_loadedFiles = array();

    private static $_aliases= array();

    public static function createAlias($class, $alias)
    {
        return class_alias($class, $alias);
    }

    public static function autoload($class)
    {
        $className = ltrim($class, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        $fileName = PRIMER_CORE . '/../' . $fileName;

        if (file_exists($fileName)) {
            require $fileName;
            return;
        }

        // Load components
        if (preg_match('#.+Component$#', $class)) {
            try {
                Primer::requireFile($class);
//                Primer::requireFile(PRIMER_CORE . DS . 'Components' . DS . $class . '.php');
                return;
            }
            catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }

        // Load controllers
        if (preg_match('#.+Controller$#', $class)) {
            try {
                Primer::requireFile(CONTROLLERS_PATH . DS . $class . '.php');
                return;
            }
            catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }

        // First attempt libs directory, then attempt Objects in libs
        $dir = scandir(PRIMER_CORE . '/lib');
        if (in_array($class . '.php', $dir)) {
            try {
                Primer::requireFile(PRIMER_CORE . DS . "lib" . DS . $class . '.php');
                return;
            }
            catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }

        // Attempt to load in Core files
        $dir = scandir(PRIMER_CORE . '/Core');
        if (in_array($class . '.php', $dir)) {
            try {
                Primer::requireFile(PRIMER_CORE . DS . "Core" . DS . $class . '.php');
                return;
            }
            catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }

        // Attempt to load in Model files
        $dir = scandir(MODELS_PATH);
        if (in_array($class . '.php', $dir)) {
            try {
                Primer::requireFile(MODELS_PATH . $class . '.php');
                return;
            }
            catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }
    }

    /**
     * Function to load in file only if it has not already been included
     * @TODO: might need to remove this. if not used properly, a file could be required in the wrong scope.
     *
     * @param $filename
     */
    public static function requireFile($filename)
    {
        if (!in_array($filename, self::$_loadedFiles)) {
            self::$_loadedFiles[] = $filename;
            require_once("$filename");
        }
    }

    /**
     * Function to determine if a file has already been included to
     * bypass expensive PHP require_once logic if it's unnecessary.
     *
     * NOTE: Once this is called with a path, that path will always return true
     * regardless of whether or not it was actually required in the code.
     *
     * @param $filename
     *
     * @return bool
     */
    public static function fileIncluded($filename)
    {
        if (!in_array($filename, self::$_loadedFiles)) {
            self::$_loadedFiles[] = $filename;
            return false;
        }
        return true;
    }

    /**
     * Function to set new values to be passed to JavaScript via RequireJS
     *
     * @param $key
     * @param $value
     * @param string $category
     */
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

    /**
     * Function to retrieve values passed from PHP to JavaScript via RequireJS
     *
     * @return mixed
     */
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

    /**
     * Global function to log any messages to a framework-specific log file
     *
     * @param $msg
     * @param string $filename
     */
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

    public static function getModelName($string)
    {
        return ucfirst(Inflector::singularize($string));
    }

    public static function isHashedArray(array $array)
    {
        // Keys of the array
        $keys = array_keys($array);

        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) !== $keys;
    }
}
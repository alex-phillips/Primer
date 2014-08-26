<?php
/**
 * @author Alex Phillips
 * Date: 3/18/14
 * Time: 12:26 PM
 */

namespace Primer\Core;

use Primer\Utility\Inflector;
use stdClass;

// dev error reporting
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
//
//if (!defined('PRIMER_CORE')) {
//    define('PRIMER_CORE', dirname(dirname(__FILE__)));
//}
//
//define('MODELS_PATH', APP_ROOT . DS . 'Models' . DS);
//define('CONTROLLERS_PATH', APP_ROOT . DS . 'Controllers' . DS);
//
//Primer::requireFile(PRIMER_CORE . DS . 'lib/Primer/Utility' .DS . 'PasswordCompatibilityLibrary.php');
//spl_autoload_register(__NAMESPACE__ . '\\Primer::autoload');

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
     * Contains files that have already been loaded
     */
    private static $_loadedFiles = array();

    private static $_aliases = array();

    public static function createAlias($class, $alias)
    {
        self::$_aliases[$alias] = $class;
//        return class_alias($class, $alias);
    }

//    public static function autoload($class)
//    {
//        if (isset(self::$_aliases[$class])) {
//            return class_alias(self::$_aliases[$class], $class);
//        }
//
//        $className = ltrim($class, '\\');
//        $fileName  = '';
//        $namespace = '';
//        if ($lastNsPos = strrpos($className, '\\')) {
//            $namespace = substr($className, 0, $lastNsPos);
//            $className = substr($className, $lastNsPos + 1);
//            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
//        }
//        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
//        $path = PRIMER_CORE . '/lib/' . $fileName;
//
//        if (file_exists($path)) {
//            require $path;
//            return;
//        }
//
//        // Load controllers
//        if (preg_match('#.+Controller$#', $class)) {
//            Primer::requireFile(CONTROLLERS_PATH . DS . $class . '.php');
//            return;
//        }
//
//        // Attempt to load in Model files
//        $dir = scandir(MODELS_PATH);
//        if (in_array($class . '.php', $dir)) {
//            Primer::requireFile(MODELS_PATH . $class . '.php');
//            return;
//        }
//    }

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
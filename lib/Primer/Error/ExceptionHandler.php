<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 11:19 AM
 */

namespace Primer\Error;

use Exception;
use Primer\Core\Application;
use Primer\Error\ExceptionRenderer;

class ExceptionHandler
{
    private static $_app;

    public static function setApp(Application $app)
    {
        static::$_app = $app;
    }

    public static function handleException(Exception $exception)
    {
//        $config = Configure::read('Exception');
//        self::_log($exception, $config);

//        $renderer = isset($config['renderer']) ? $config['renderer']
//            : 'ExceptionRenderer';
//        if ($renderer !== 'ExceptionRenderer') {
//            list($plugin, $renderer) = pluginSplit($renderer, true);
//            App::uses($renderer, $plugin . 'Error');
//        }
        try {
            $error = new ExceptionRenderer($exception, static::$_app);
            $error->render();
        } catch (Exception $e) {
//            set_error_handler(
//                Configure::read('Error.handler')
//            ); // Should be using configured ErrorHandler
            $message = sprintf(
                "[%s] %s\n%s", // Keeping same message format
                get_class($e),
                $e->getMessage(),
                $e->getTraceAsString()
            );
            trigger_error($message, E_USER_ERROR);
        }
    }
}
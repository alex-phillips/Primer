<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/22/14
 * Time: 6:06 PM
 */

namespace Primer\Proxy;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log extends Proxy
{
    private static $_channels = array();

    protected static function getProxyAccessor()
    {
        return 'logger';
    }

    public static function record($message, $file, $level = 'info', $context = array())
    {
        if (!array_key_exists($file, self::$_channels)) {
            $logger = new Logger($file);
            if (static::$_app['config']['app.log_daily_files'] === true) {
                $logger->pushHandler(new RotatingFileHandler(LOG_PATH . $file, 7));
            }
            else {
                $logger->pushHandler(new StreamHandler(LOG_PATH . $file));
            }

            self::$_channels[$file] = $logger;
        }

        self::$_channels[$file]->$level($message, $context);
    }
}
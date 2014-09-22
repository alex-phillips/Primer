<?php

namespace Primer\Core;

use Primer\Utility\Inflector;

/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/3/14
 * Time: 1:52 PM
 *
 * This is the object that every object in Primer is extended from. It allows
 * all classes to share functions across the entire application.
 */
abstract class Object
{
    public function getControllerName($string)
    {
        return ucfirst(Inflector::pluralize($string) . 'Controller');
    }

    public function getModelName($string)
    {
        return ucfirst(Inflector::singularize($string));
    }

    public function logMessage($msg, $filename = 'core.log')
    {
        $pid = getmypid();
        $dt = date("Y-m-d H:i:s (T)");
        $fullpath = LOG_PATH . $filename;
        error_log("$dt\t$pid\t$msg\n", 3, $fullpath);
    }
}

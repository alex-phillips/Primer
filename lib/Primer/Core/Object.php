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
    public static function getControllerName($string = null)
    {
        $string = $string ?: get_called_class();

        return ucfirst(Inflector::pluralize($string) . 'Controller');
    }

    public static function getModelName($string = null)
    {
        $string = $string ?: get_called_class();

        return Inflector::classify($string);
    }
}

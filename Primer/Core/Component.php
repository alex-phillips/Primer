<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 5/18/14
 * Time: 11:55 AM
 */

class Component
{
    protected function __construct()
    {

    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }
}
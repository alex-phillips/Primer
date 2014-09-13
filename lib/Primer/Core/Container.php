<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/12/14
 * Time: 6:12 PM
 */

namespace Primer\Core;

use ArrayAccess;

abstract class Container extends Object implements ArrayAccess
{
    protected $_bindings = array();
    protected $_aliases = array();

    public function bind($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf(
                'Cannot override service "%s"',
                $key
            ));
        }

        if (!is_callable($o)) {
            throw new \RuntimeException(sprintf(
                'Binding is not a valid callable for "%s"',
                $key
            ));
        }

        $this->_bindings[$key] = $o;
    }

    public function instance($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf(
                'Cannot override service "%s"',
                $key
            ));
        }

        $this->_bindings[$key] = $o;
    }

    public function singleton($key, $o = null)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf(
                'Cannot override service "%s"',
                $key
            ));
        }

        if (!is_callable($o)) {
            $this->_bindings[$key] = new $key();
        }
        else {
            $this->_bindings[$key] = call_user_func($o, $this);
        }
    }

    public function alias($alias, $binding)
    {
        $this->_aliases[$alias] = $binding;
    }

    public function make($key, $parameters = array())
    {
        if (array_key_exists($key, $this->_aliases)) {
            $key = $this->_aliases[$key];
            return $this->make($key);
        }

        if (!array_key_exists($key, $this->_bindings)) {
            $parameters = array();

            $class = new \ReflectionClass($key);
            $method = $class->getMethod('__construct');
            $classParams = $method->getParameters();

            foreach ($classParams as $param) {
                try {
                    $parameters[] = $this->make($param->getClass()->getName());
                } catch (\ReflectionException $e) {
                    echo "Class $key does not exist";
                    return null;
                }
            }

            $newInstance = new \ReflectionClass($key);
            $newInstance = $newInstance->newInstanceArgs($parameters);
            return $newInstance;
        }

        if (is_callable($this->_bindings[$key])) {
            return call_user_func(
                $this->_bindings[$key],
                $this,
                $parameters
            );
        }

        if (isset($this->_bindings[$key])) {
            return $this->_bindings[$key];
        }
    }

    public function offsetExists($key)
    {
        return (isset($this->_bindings[$key]));
    }

    public function offsetGet($key)
    {
        $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        if (!$value instanceof Closure) {
            $value = function () use ($value) {
                return $value;
            };
        }

        $this->bind($key, $value);
    }

    public function offsetUnset($key)
    {
        unset($this->_bindings[$key]);
    }
}
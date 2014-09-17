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
            $this->_bindings[$key] = $this->make($key);
        }
        else {
            $this->_bindings[$key] = call_user_func($o, $this);
        }
    }

    public function alias($alias, $binding)
    {
        $this->_aliases[$alias] = $binding;
    }

    public function make($key, $dependencies = array())
    {
        if (array_key_exists($key, $this->_aliases)) {
            $key = $this->_aliases[$key];
            return $this->make($key);
        }

        if (!array_key_exists($key, $this->_bindings)) {
            $dependencies = array();

            $reflector = new \ReflectionClass($key);
            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                return new $key();
            }

            $classParams = $constructor->getParameters();

            foreach ($classParams as $param) {
                $dependency = $param->getClass();

                if (!is_null($dependency)) {
                    if ($param->isDefaultValueAvailable()) {
                        $dependencies[] = $param->getDefaultValue();
                    }
                    else {
                        try {
                            $dependencies[] = $this->make($param->getClass()->name);
                        }
                        catch (\Exception $e) {
                            if ($param->isOptional()) {
                                $dependencies[] = $param->getDefaultValue();
                            }
                            else {
                                throw $e;
                            }
                        }
                    }
                }
            }

            return $reflector->newInstanceArgs($dependencies);
        }

        if (is_callable($this->_bindings[$key])) {
            return call_user_func(
                $this->_bindings[$key],
                $this,
                $dependencies
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
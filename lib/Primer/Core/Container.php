<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/12/14
 * Time: 6:12 PM
 */

namespace Primer\Core;

use ArrayAccess;
use Exception;

abstract class Container extends Object implements ArrayAccess
{
    protected $_bindings = array();
    protected $_instances = array();
    protected $_aliases = array();

    public function bind($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot override service "%s"',
                    $key
                )
            );
        }

        if (!is_callable($o)) {
            throw new \RuntimeException(
                sprintf(
                    'Binding is not a valid callable for "%s"',
                    $key
                )
            );
        }

        $this->_bindings[$key] = $o;
    }

    public function instance($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot override service "%s"',
                    $key
                )
            );
        }

        $this->_instances[$key] = $o;
    }

    public function singleton($key, $o = null)
    {
        if (array_key_exists($key, $this->_instances)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot override service "%s"',
                    $key
                )
            );
        }

        $this->_instances[$key] = $o;
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

        if (array_key_exists($key, $this->_instances)) {
            if (is_callable($this->_instances[$key])) {
                $this->_instances[$key] = call_user_func(
                    $this->_instances[$key], $this
                );
            }
            else {
                if ($this->_instances[$key] === null) {
                    $this->_instances[$key] = $this->_buildClass($key);
                }
            }

            return $this->_instances[$key];
        }

        if (!array_key_exists($key, $this->_bindings)) {
            return $this->_buildClass($key);
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

    private function _buildClass($key)
    {
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
                    } catch (Exception $e) {
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

    abstract public function offsetExists($key);

    abstract public function offsetGet($key);

    abstract public function offsetSet($key, $value);

    abstract public function offsetUnset($key);
}
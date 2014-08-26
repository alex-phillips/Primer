<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/11/14
 * Time: 9:01 AM
 */

namespace Primer\IOC;

use Primer\Core\Primer;
use Primer\Utility\ParameterContainer;

class IOC implements \ArrayAccess
{
    private $_bindings = array();
    private $_aliases = array();

    public function __construct()
    {
        $this->_bindings = new ParameterContainer();
    }

    public function bind($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        if (!is_callable($o)) {
            throw new \RuntimeException(sprintf('Binding is not a valid callable for "%s"', $key));
        }

        $this->_bindings[$key] = $o;
    }

    public function singleton($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        if (!is_callable($o)) {
            throw new \RuntimeException(sprintf('Binding is not a valid callable for "%s"', $key));
        }

        $this->_bindings[$key] = call_user_func($o, $this);
    }

    public function instance($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        $this->_bindings[$key] = $o;
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
//                    echo "Class $key does not exist";
//                    return null;
                }
            }

            $newInstance = new \ReflectionClass($key);
            $newInstance = $newInstance->newInstanceArgs($parameters);
            return $newInstance;
        }

        if ($this->_bindings[$key] instanceof Closure) {
            return call_user_func($this->_bindings->get($key), $this, $parameters);
        }

        return $this->_bindings->get($key);
    }

    public function alias($alias, $binding)
    {
        Primer::createAlias($binding, $alias);
        $this->_aliases[$alias] = $binding;
    }

    public function offsetExists($key)
    {
        return $this->_bindings->get($key);
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->_bindings->set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->_bindings->delete($key);
    }
}
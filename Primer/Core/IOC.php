<?php
/**
 * Created by IntelliJ IDEA.
 * User: exonintrendo
 * Date: 8/11/14
 * Time: 9:01 AM
 */

namespace Primer\Core;

class IOC
{
    private $_bindings = array();
    private $_aliases = array();

    public function __construct()
    {

    }

    public function bind($key, $o)
    {
        if (array_key_exists($key, $this->_bindings)) {
            throw new \RuntimeException(sprintf('Cannot override service "%s"', $key));
        }

        if (!($o instanceof Closure)) {
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

        if ($this->_bindings[$key] instanceof Closure) {
            return call_user_func($this->_bindings[$key], $this, $parameters);
        }

        return $this->_bindings[$key];
    }

    public function alias($alias, $binding)
    {
        $this->_aliases[$alias] = $binding;
    }
}
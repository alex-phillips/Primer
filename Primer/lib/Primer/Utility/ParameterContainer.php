<?php
/**
 * @author Alex Phillips
 * @date 6/28/14
 * @time 2:22 PM
 */

namespace Primer\Utility;

/**
 * Class ParameterContainer
 */
class ParameterContainer implements \ArrayAccess
{
    /*
     * Parameters array that contains all accessible values
     */
    protected $_parameters = array();

    /**
     * Set the class's parameters to an passed in array
     *
     * @param array $parameters
     */
    public function __construct($parameters = array())
    {
        $this->_parameters = $parameters;
    }

    /**
     * Returns true if the class's parameters contains a value
     * for a given key. The key can be a '.' delimited array path.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        if ($this->get($key)) {
            return true;
        }

        return false;
    }

    /**
     * Return a value from the class's parameters given a key.
     * The key can be a '.' delimited array path.
     *
     * @param $key
     *
     * @return array|null
     */
    public function get($key)
    {
        $path = explode('.', $key);
        $ary = & $this->_parameters;
        foreach ($path as $p) {
            if (!isset ($ary[$p])) {
                return null;
            }
            $ary = & $ary[$p];
        }

        return $ary;
    }

    public function offsetExists($key)
    {
        return $this->get($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Set a value in the class's parameters given a key.
     * The key can be a '.' delimited array path.
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $path = explode('.', $key);
        $ary = & $this->_parameters;
        foreach ($path as $p) {
            if (!isset ($ary[$p])) {
                $ary[$p] = array();
            }
            $ary = & $ary[$p];
        }

        $ary = $value;
    }

    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    /**
     * Unset a value in the class's parameters given a '.'
     * delimited array path.
     *
     * @param $key
     */
    public function delete($key)
    {
        $path = explode('.', $key);
        $ary = & $this->_parameters;
        foreach ($path as $p) {
            if (!isset ($ary[$p])) {
                $ary[$p] = array();
            }
            $key = & $ary;
            $ary = & $ary[$p];
        }

        unset($key[$p]);
    }
}
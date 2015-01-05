<?php
/**
 * DefinedInput
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 */

namespace Primer\Console\Input;

use Primer\Console\Exception\DefinedInputException;

abstract class DefinedInput
{
    const VALUE_NONE = 1;
    const VALUE_REQUIRED = 2;
    const VALUE_OPTIONAL = 3;

    protected $_name;
    protected $_aliases = array();
//    protected $_valueRequirement;
    protected $_description = '';
    protected $_default = false;
    protected $_value = null;

    public function getName()
    {
        return $this->_name;
    }

    public function getShortName()
    {
        if (strlen($this->_name) === 1) {
            return $this->_name;
        }

        foreach ($this->_aliases as $alias) {
            if (strlen($alias) === 1) {
                return $alias;
            }
        }

        return null;
    }

    public function getLongName()
    {
        if (strlen($this->_name) > 1) {
            return $this->_name;
        }

        foreach ($this->_aliases as $alias) {
            if (strlen($alias) > 1) {
                return $alias;
            }
        }

        return null;
    }

    public function getNames()
    {
        return array_merge(array($this->_name), $this->_aliases);
    }

    public function getDescription()
    {
        return $this->_description;
    }

//    public function getValueRequirement()
//    {
//        return $this->_valueRequirement;
//    }

    public function getDefault()
    {
        return $this->_default;
    }

    public function isFitAnyParameter($parameterName)
    {
        if ($parameterName === $this->_name || in_array($parameterName, $this->_aliases)) {
            return true;
        }

        return false;
    }

    public function getSettings()
    {
        $settings = array();
        foreach ($this as $k => $v) {
            $k = ltrim($k, '_');
            $settings[$k] = $v;
        }

        return $settings;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setValue($value)
    {
        $this->_value = $value;
    }
}
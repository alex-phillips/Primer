<?php
/**
 * DefinedInput
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console\Input;

use Primer\Console\Exception\DefinedInputException;

class DefinedInput
{
    const VALUE_NONE = 1;
    const VALUE_REQUIRED = 2;
    const VALUE_OPTIONAL = 3;

    private $_shortName;
    private $_longName;
    private $_valueRequirement;
    private $_description = '';

    public function addParameter($shortName = null, $longName = null, $valueRequirement = self::VALUE_NONE, $description = '')
    {
        if (!$shortName && !$longName) {
            throw new DefinedInputException();
        }

        $this->_shortName = $shortName;
        $this->_longName = $longName;
        $this->_valueRequirement = $valueRequirement;
        $this->_description = $description;
    }

    public function getValidName()
    {
        if (!$this->_shortName) {
            return $this->_longName;
        }

        return $this->_shortName;
    }

    public function getShortName()
    {
        return $this->_shortName;
    }

    public function getLongName()
    {
        return $this->_longName;
    }

    public function getNames()
    {
        $names = array();
        if ($this->_shortName) {
            $names[] = $this->_shortName;
        }
        if ($this->_longName) {
            $names[] = $this->_longName;
        }

        return $names;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getValueRequirement()
    {
        return $this->_valueRequirement;
    }

    public function isFitAnyParameter($parameterName)
    {
        switch ($parameterName) {
            case $this->_longName:
            case $this->_shortName: {
                return true;
            }
                break;
        }

        return false;
    }

    public function getOppositeParameter($actualParameter)
    {
        if ($actualParameter == $this->_longName) {
            return $this->_shortName;
        }
        else {
            return $this->_longName;
        }
    }
}
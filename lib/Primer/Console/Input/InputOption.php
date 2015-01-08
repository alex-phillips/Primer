<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 1/3/15
 * Time: 11:35 AM
 */

namespace Primer\Console\Input;

use Primer\Console\Exception\DefinedInputException;

class InputOption extends DefinedInput
{
    public function __construct($name, $aliases = array(), $mode = null, $description = '', $default = null)
    {
        if (!$name) {
            throw new DefinedInputException();
        }

        if (is_string($aliases)) {
            $aliases = array(
                $aliases,
            );
        }

        $this->_name = $name;
        foreach ($aliases as $index => $alias) {
            if (strlen($alias) > strlen($this->_name)) {
                $aliases[$index] = $this->_name;
                $this->_name = $alias;
            }
        }
        $this->_aliases = array_unique($aliases);

        if (!$mode) {
            $mode = DefinedInput::VALUE_OPTIONAL;
        }

        $this->_mode = $mode;
        $this->_description = $description;
        $this->_default = $default;
    }
}
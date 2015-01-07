<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 1/6/15
 * Time: 6:42 PM
 */

namespace Primer\Console\Input;

class InputArgument extends DefinedInput
{
    public function __construct($name, $mode = DefinedInput::VALUE_REQUIRED, $description = '', $default = null)
    {
        $this->_name = $name;
        $this->_mode = $mode;
        $this->_description = $description;
        $this->_default = $default;
    }
}
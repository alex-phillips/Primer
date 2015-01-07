<?php
/**
 * PHP Command Line Tools
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 *
 * @author    James Logsdon <dwarf@girsbrain.org>
 * @copyright 2010 James Logsdom (http://girsbrain.org)
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Primer\Console;

use cli\arguments\Argument;
use Primer\Console\Arguments\Arguments;
use Primer\Console\Arguments\ArgumentBag;

/**
 * Arguments help screen renderer
 */
class HelpScreen
{
    /**
     * Data structure of available flags to output
     *
     * @var array
     */
    protected $_flags = array();

    /**
     * Max length needed for 'flags' to be displayed before descriptions
     *
     * @var int
     */
    protected $_flagMax = 0;

    /**
     * Data structure of available options to output
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Max length needed for 'options' to be displayed before descriptions
     *
     * @var int
     */
    protected $_optionMax = 0;

    /**
     * Data structure of available commands to output
     *
     * @var array
     */
    protected $_commands = array();

    /**
     * Max length needed for 'commands' to be displayed before descriptions
     *
     * @var int
     */
    protected $_commandMax = 0;

    protected $_arguments = array();

    protected $_argumentMax = 0;

    public function __construct(Arguments $arguments = null)
    {
        if ($arguments) {
            $this->set($arguments);
        }
    }

    /**
     * Output the class to render the help screen
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Pass in Arguments object to set available flags, options, and commands
     *
     * @param Arguments $arguments
     */
    public function set(Arguments $arguments)
    {
        $this->setArguments($arguments->getArguments());
        $this->setFlags($arguments->getFlags());
        $this->setOptions($arguments->getOptions());
        $this->setCommands($arguments->getCommands());
    }

    public function setArguments(ArgumentBag $arguments)
    {
        $max = 0;
        $out = array();

        foreach ($arguments as $name => $argument) {
            $max = max(strlen($name), $max);
            $out[$name] = $argument->getSettings();
        }

        $this->_arguments = $out;
        $this->_argumentMax = $max;
    }

    /**
     * Individually set flags given an ArgumentBag
     *
     * @param ArgumentBag $flags
     */
    public function setFlags(ArgumentBag $flags)
    {
        $data = $this->_consume($flags);

        $this->_flags = $data[0];
        $this->_flagMax = $data[1];
    }

    /**
     * Individually set options given an ArgumentBag
     *
     * @param ArgumentBag $options
     */
    public function setOptions(ArgumentBag $options)
    {
        $data = $this->_consume($options);

        $this->_options = $data[0];
        $this->_optionMax = $data[1];
    }

    /**
     * Individually set commands given an ArgumentBag
     *
     * @param ArgumentBag $commands
     */
    public function setCommands(ArgumentBag $commands)
    {
        $max = 0;
        $out = array();

        foreach ($commands as $command => $argument) {
            $max = max(strlen($command), $max);
            $out[$command] = $argument->getSettings();
        }

        $this->_commands = $out;
        $this->_commandMax = $max;
    }

    /**
     * Return output for the help screen given the provided flags, options,
     * and commands available
     *
     * @param bool $flags
     * @param bool $options
     * @param bool $commands
     *
     * @return string
     */
    public function render($arguments = true, $flags = true, $options = true, $commands = true)
    {
        $help = array();

        if ($arguments) {
            if ($output = $this->_renderArguments()) {
                array_push($help, $output);
            }
        }
        if ($flags) {
            if ($output = $this->_renderFlags()) {
                array_push($help, $output);
            }
        }
        if ($options) {
            if ($output = $this->_renderOptions()) {
                array_push($help, $output);
            }
        }
        if ($commands) {
            if ($output = $this->_renderCommands()) {
                array_push($help, $output);
            }
        }

        return join($help, "\n\n") . "\n";
    }

    private function _renderArguments()
    {
        if (empty($this->_arguments)) {
            return null;
        }

        return "<warning>Arguments</warning>\n" . $this->_renderScreen($this->_arguments, $this->_argumentMax);
    }

    private function _renderFlags()
    {
        if (empty($this->_flags)) {
            return null;
        }

        return "<warning>Flags</warning>\n" . $this->_renderScreen($this->_flags, $this->_flagMax);
    }

    private function _renderOptions()
    {
        if (empty($this->_options)) {
            return null;
        }

        return "<warning>Options</warning>\n" . $this->_renderScreen(
            $this->_options, $this->_optionMax
        );
    }

    private function _renderCommands()
    {
        if (empty($this->_commands)) {
            return null;
        }

        return "<warning>Commands</warning>\n" . $this->_renderScreen(
            $this->_commands, $this->_commandMax
        );
    }

    private function _renderScreen($options, $max)
    {
        $help = array();
        foreach ($options as $option => $settings) {
            $formatted = '  <info>' . str_pad($option, $max) . '</info>';

            $dlen = 80 - 4 - $max;

            $description = str_split($settings['description'], $dlen);
            $formatted .= '  ' . array_shift($description);

            if (isset($settings['default']) && $settings['default']) {
                $formatted .= ' [default: ' . $settings['default'] . ']';
            }

            // Pad was originally 3, since I'm indenting the lines by default, increased
            // to 4.
            $pad = str_repeat(' ', $max + 4);
            while ($desc = array_shift($description)) {
                $formatted .= "\n${pad}${desc}";
            }

            $formatted = "$formatted";

            array_push($help, $formatted);
        }

        return join($help, "\n");
    }

    private function _consume($options)
    {
        $max = 0;
        $out = array();

        foreach ($options as $name => $argument) {
            $names = array();
            foreach ($argument->getNames() as $alias) {
                switch (strlen($alias)) {
                    case 1:
                        $alias = "-$alias";
                        break;
                    default:
                        $alias = "--$alias";
                        break;
                }
                array_push($names, $alias);
            }

            $names = join($names, ', ');
            $max = max(strlen($names), $max);
            $out[$names] = $argument->getSettings();
        }

        return array($out, $max);
    }
}
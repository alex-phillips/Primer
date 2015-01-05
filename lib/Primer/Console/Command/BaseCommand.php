<?php
/**
 * BaseCommand
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 */

namespace Primer\Console\Command;

use Primer\Console\Arguments\ArgumentBag;
use Primer\Console\Arguments\Arguments;
use Primer\Console\ConsoleObject;
use Primer\Console\HelpScreen;
use Primer\Console\Input\DefinedInput;
use Primer\Console\Output\Writer;

abstract class BaseCommand extends ConsoleObject
{
    public $args;
    protected $_name = '';
    protected $_description = '';

    /**
     * @var DefinedInput[]
     */
    private $_userDefinedInput;
    private $_aliases = array();

    public function __construct()
    {
        parent::__construct();

        $this->args = new Arguments();
        $this->_userDefinedInput = new ArgumentBag();
    }

    abstract public function configure();

    abstract public function run();

    public function setup($args)
    {
        $this->args->addFlags($args->getFlags());
        $this->args->addOptions($args->getOptions());
        $this->args->addCommand($this->getName());
    }

    public function prepare()
    {
        $this->args->parse();
        $this->_setOutputSystemVerbosity();
    }

    private function _setOutputSystemVerbosity()
    {
        if ($this->_isVerboseSet()) {
            $this->_stdout->setApplicationVerbosity(Writer::VERBOSITY_VERBOSE);
        }
        else {
            if ($this->_isQuietSet()) {
                $this->_stdout->setApplicationVerbosity(Writer::VERBOSITY_QUIET);
            }
            else {
                $this->_stdout->setApplicationVerbosity(Writer::VERBOSITY_NORMAL);
            }
        }
    }

    private function _isVerboseSet()
    {
        return ($this->args['verbose'] || $this->args['v']);
    }

    private function _isQuietSet()
    {
        return ($this->args['quiet'] || $this->args['q']);
    }

    public function verify()
    {
        return true;

        // Make sure passed arguments match value requirements
        $this->args->addCommand($this->getName());
        $this->args->parse();
        foreach ($this->_userDefinedInput as $name => $argument) {
            switch ($argument->getValueRequirement()) {
                case DefinedInput::VALUE_REQUIRED:
                    $value = $this->args->getParsedOption($name);
                    if (!$value || is_bool($value->getValue())) {
                        // @TODO: throw exception (and properly handle exception output)
                        return false;
                    }
                    break;
                case DefinedInput::VALUE_NONE:
                    if (!$this->args->getParsedFlag($name)) {
                        // @TODO: throw exception (and properly handle exception output)
                        return false;
                    }
                    break;
                default:
                    break;
            }
        }

        return true;
    }

    public function getParameterValue($input)
    {
        if ($input instanceof DefinedInput) {
            if (isset($this->args[$input->getShortName()])) {
                return $this->args[$input->getShortName()];
            }

            if (isset($this->args[$input->getLongName()])) {
                return $this->args[$input->getLongName()];
            }
        }
        else {
            if ($this->_isParameterFitUserDefined($input)) {
                if (isset($this->args[$input])) {
                    return $this->args[$input];
                }

                return $this->args[$this->_fitParameterName($input)];
            }
        }

        return null;
    }

    private function _isParameterFitUserDefined($parameterName)
    {
        foreach ($this->_userDefinedInput as $singleUserDefinedInput) {
            if ($singleUserDefinedInput->isFitAnyParameter($parameterName)) {
                return true;
            }
        }

        return false;
    }

    private function _fitParameterName($originalParameter)
    {
        foreach ($this->_userDefinedInput as $singleUserDefinedInput) {
            if ($singleUserDefinedInput->isFitAnyParameter($originalParameter)) {
                return $singleUserDefinedInput->getOppositeParameter($originalParameter);
            }
        }
    }

    public function getUsage()
    {
        $applicationName = implode(', ', $this->_aliases);

        $arguments = '';
        foreach ($this->_userDefinedInput as $input) {
            $names = array();
            if ($input->getShortName()) {
                $names[] = "-" . $input->getShortName();
            }
            if ($input->getLongName()) {
                $names[] = "--" . $input->getLongName();
            }

            $names = implode(', ', $names);

            $arguments .= "\t$names\n\t\t$this->_description";
        }

        $message = <<<__USAGE__
NAME
    {$applicationName}

DESCRIPTION
    {$this->_description}
__USAGE__;

        return $message;
    }

    public function addFlag($name, $aliases = array(), $description = '', $stackable = false)
    {
        $this->args->addFlag($name, $aliases, $description, $stackable);
    }

    public function addOption($name, $aliases = array(), $default = null, $description = '')
    {
        $this->args->addOption($name, $aliases, $default, $description);
    }

    public function getFlag($flag)
    {
        if ($val = $this->args->getParsedFlag($flag)) {
            return $val->getValue();
        }

        return null;
    }

    public function getOption($option)
    {
        if ($val = $this->args->getParsedOption($option)->getValue()) {
            return $val->getValue();
        }

        return null;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setDescription($description)
    {
        $this->_description = $description;

        return $this;
    }

    public function getDefinedParameters()
    {
        return $this->_userDefinedInput;
    }

    public function renderHelpScreen()
    {
        $helpScreen = new HelpScreen();
        $helpScreen->setFlags($this->args->getFlags());
        $helpScreen->setOptions($this->_userDefinedInput);

        $this->displayUsage();
        $this->line();
        $this->out("<warning>Description</warning>");
        $this->out("  <info>{$this->getDescription()}</info>");
        $this->line();
        $this->out($helpScreen->render(true, true, false));
    }

    protected function displayUsage()
    {
        $this->out("<warning>Usage</warning>");

        $usage = array($this->getName());
        foreach ($this->_userDefinedInput as $argument) {

        }
        $usage = implode(" ", $usage);

        $this->out("  <info>$usage</info>");
    }
}
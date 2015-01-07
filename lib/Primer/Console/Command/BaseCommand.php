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

    /**
     * Any setup necessary prior for the command to run, i.e. setting the name,
     * description, and any necessary arguments
     *
     * @return mixed
     */
    abstract public function configure();

    /**
     * All main logic of the command to be executed
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * This function passes the arguments parsed from the main application to be
     * accessible by the command
     *
     * @param $args
     */
    public function setup($args)
    {
        $this->args->addFlags($args->getFlags());
        $this->args->addOptions($args->getOptions());
        $this->args->addCommand($this->getName());
    }

    public function prepare()
    {
        $this->args->parse();
        $this->checkArguments();
        $this->_setOutputSystemVerbosity();
    }

    private function checkArguments()
    {
        foreach ($this->args->getArguments() as $name => $argument) {
            if ($argument->getMode() === DefinedInput::VALUE_REQUIRED && $argument->getValue() === null) {
                throw new \InvalidArgumentException;
            }
        }
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

    /**
     * Function to determine if the 'verbosity' flag has been set
     *
     * @return bool
     */
    private function _isVerboseSet()
    {
        return ($this->args['verbose'] || $this->args['v']);
    }

    /**
     * Function to determine if the 'quiet' flag has been set
     *
     * @return bool
     */
    private function _isQuietSet()
    {
        return ($this->args['quiet'] || $this->args['q']);
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

    public function addFlag($flag, $aliases = array(), $description = '', $stackable = false)
    {
        call_user_func_array(array($this->args, 'addFlag'), func_get_args());
    }

    public function addOption($option, $aliases = array(), $default = null, $description = '')
    {
        call_user_func_array(array($this->args, 'addOption'), func_get_args());
    }

    public function addArgument($name, $mode = DefinedInput::VALUE_REQUIRED, $description = '')
    {
        call_user_func_array(array($this->args, 'addArgument'), func_get_args());
    }

    public function getFlag($flag)
    {
        if ($this->args->flagExists($flag)) {
            return $this->args->getFlag($flag)->getValue();
        }

        return null;
    }

    public function getOption($option)
    {
        if ($this->args->optionExists($option)) {
            $this->args->getOption($option)->getValue();
        }

        return null;
    }

    public function getArgument($name)
    {
        if ($val = $this->args->getParsedArgument($name)) {
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
        foreach ($this->args->getArguments() as $name => $argument) {
            switch ($argument->getMode()) {
                case DefinedInput::VALUE_OPTIONAL:
                    $usage[] = "[{$argument->getName()}]";
                    break;
                case DefinedInput::VALUE_REQUIRED:
                    $usage[] = $argument->getName();
                    break;
            }
        }
        $usage = implode(" ", $usage);

        $this->out("  <info>$usage</info>");
    }
}
<?php
/**
 * Shell
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console;

use Primer\Console\Helpers\Helper;
use Primer\Console\Input\DefinedInput;
use Primer\Console\Output\Writer;
use Primer\Console\Exception\DefinedInputException;

class BaseCommand extends ConsoleObject
{
    public $parsedArgv;

    private $_applicationOptions = array();
    /**
     * @var DefinedInput[]
     */
    private $_userDefinedInput = array();

    private $_aliases = array();

    protected $_description = '';

    public function configure() {}

    public function setup($aliases, $args, $applicationOptions)
    {
        $this->_aliases = $aliases;
        $this->parsedArgv = $args;
        $this->_applicationOptions = $applicationOptions;
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
        return array_key_exists('verbose', $this->_applicationOptions);
    }

    private function _isQuietSet()
    {
        return (array_key_exists('quiet', $this->_applicationOptions) || array_key_exists('q', $this->_applicationOptions));
    }

    public function verify()
    {
        // Make sure passed arguments match value requirements
        foreach ($this->_userDefinedInput as $input) {
            $value = $this->getParameterValue($input);
            switch ($input->getValueRequirement()) {
                case DefinedInput::VALUE_NONE:
                    if ($value !== null && $value !== true) {
                        throw new DefinedInputException;
                    }
                    break;
                case DefinedInput::VALUE_OPTIONAL:
                    break;
                case DefinedInput::VALUE_REQUIRED:
                    if ($value === null || $value === true) {
                        throw new DefinedInputException;
                    }
                    break;
            }
        }

        // Make sure that each argument passed is supported
        foreach ($this->parsedArgv as $arg => $val) {
            if ($arg === 'h' || $arg === 'help') {
                continue;
            }

            if (is_numeric($arg)) {
                if (!$this->_isParameterFitUserDefined($val)) {
                    throw new DefinedInputException();
                }
            }
            else {
                if (!$this->_isParameterFitUserDefined($arg)) {
                    throw new DefinedInputException();
                }
            }
        }

        if (isset($this->parsedArgv['h']) || isset($this->parsedArgv['help'])) {
            $this->out($this->getUsage());
            exit(1);
        }
    }

    public function addParameter($shortName, $longName, $valueRequirement = DefinedInput::VALUE_OPTIONAL, $description = '')
    {
        $definedInput = new DefinedInput();
        $definedInput->addParameter($shortName, $longName, $valueRequirement, $description);
        $this->_userDefinedInput[] = $definedInput;

        return $this;
    }

    public function getParameterValue($input)
    {
        if ($input instanceof DefinedInput) {
            if (isset($this->parsedArgv[$input->getShortName()])) {
                return $this->parsedArgv[$input->getShortName()];
            }

            if (isset($this->parsedArgv[$input->getLongName()])) {
                return $this->parsedArgv[$input->getLongName()];
            }
        }
        else {
            if ($this->_isParameterFitUserDefined($input)) {
                if (isset($this->parsedArgv[$input])) {
                    return $this->parsedArgv[$input];
                }

                return $this->parsedArgv[$this->_fitParameterName($input)];
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

    public function getHelper($helperName)
    {
        return Helper::loadHelper($helperName);
    }

    public function setDescription($description)
    {
        $this->_description = $description;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getDefinedParameters()
    {
        return $this->_userDefinedInput;
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
}
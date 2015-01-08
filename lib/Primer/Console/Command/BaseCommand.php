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
    private $_userDefinedFlags;
    private $_userDefinedOptions;

    public function __construct()
    {
        parent::__construct();

        $this->args = new Arguments();
        $this->_userDefinedFlags = new ArgumentBag();
        $this->_userDefinedOptions = new ArgumentBag();
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

    public function addFlag($flag, $aliases = array(), $description = '', $stackable = false)
    {
        call_user_func_array(array($this->args, 'addFlag'), func_get_args());
        if ($flag instanceof DefinedInput) {
            $flag = $flag->getName();
        }
        $this->_userDefinedFlags[$flag] = $this->args->getFlag($flag);
    }

    public function addOption($option, $aliases = array(), $mode = null, $description = '', $default = null)
    {
        call_user_func_array(array($this->args, 'addOption'), func_get_args());
        if ($option instanceof DefinedInput) {
            $option = $option->getName();
        }
        $this->_userDefinedOptions[$option] = $this->args->getOption($option);
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

    public function getUserDefinedFlags()
    {
        return $this->_userDefinedFlags;
    }

    public function getUserDefinedOptions()
    {
        return $this->_userDefinedOptions;
    }

    public function renderHelpScreen()
    {
        $helpScreen = new HelpScreen($this->args);
        $this->out($helpScreen->render($this));
        $this->out("<warning>Help</warning>");
        $this->out("  {$this->getDescription()}");
    }
}
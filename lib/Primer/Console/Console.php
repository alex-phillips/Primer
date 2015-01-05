<?php
/**
 * Console
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 */

namespace Primer\Console;

use Primer\Console\Command\BaseCommand;
use Primer\Console\Arguments\Arguments;

class Console extends ConsoleObject
{
    /**
     * Application instance used for content injection, exception handling, etc.
     *
     * @var
     */
    private $_app;

    /**
     * Name of the application.
     *
     * @var string
     */
    private $_applicationName;

    /**
     * Object that holds all possible arguments as well as parsed arguments
     *
     * @var Arguments
     */
    private $_arguments;

    /**
     * Data structure to hold all avaiable command instances
     *
     * @var array
     */
    private $_commands = array();

    /**
     * All available flags that can be used application-wide
     *
     * @var array
     */
    private $_flags = array(
        'help'    => array(
            'description' => 'Display this help message',
            'aliases'     => array('h'),
        ),
        'quiet'   => array(
            'description' => 'Suppress output',
            'aliases'     => array('q'),
        ),
        'verbose' => array(
            'description' => 'Set level of output verbosity',
            'aliases'     => array('v'),
            'stackable'   => true,
        ),
    );

    public function __construct($applicationName = '', $argv = null)
    {
        if (!$argv) {
            $argv = $_SERVER['argv'];
        }

        $this->_applicationName = $applicationName;
        $this->_userPassedArgv = $argv;

        $this->_arguments = new Arguments(array(
            'flags' => $this->_flags,
        ));

        parent::__construct();
    }

    public function addCommand(BaseCommand $instance)
    {
        $instance->setup($this->_arguments);
        $instance->configure();
        $this->_commands[$instance->getName()] = $instance;
        $this->_arguments->addCommand($instance->getName(), $instance->getDescription());
    }

    public function run()
    {
        $this->_arguments->parse();
        $parsedCommands = $this->_arguments->getParsedArguments();
        if (count($parsedCommands) === 1) {
            $this->_callApplication($parsedCommands[0]);
        }
        else {
            $this->_buildHelpScreen();
        }
    }

    private function _callApplication($applicationName)
    {
        if (!array_key_exists($applicationName, $this->_commands)) {
            $this->_buildHelpScreen();
        }
        else {
            $command = $this->_commands[$applicationName];

            if ($this->_arguments->getParsedFlag('h')) {
                $command->renderHelpScreen();
            }
            else {
                $command->run();
            }
        }
    }

    private function _buildHelpScreen()
    {
        $helpScreen = new HelpScreen($this->_arguments);
        $this->out($this->_applicationName . "\n\n");
        $this->out($helpScreen->render());
    }

    public function setApp($app)
    {
        $this->_app = $app;
    }
}

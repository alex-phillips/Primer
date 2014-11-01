<?php
/**
 * Console
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 */

namespace Primer\Console;

use Primer\Console\Input\ArgumentParser;

class Console extends ConsoleObject
{
    /*
     * Name of the application.
     */
    private $_applicationName;

    /*
     * Version of the application.
     */
    private $_applicationVersion;

    /*
     * Array of parsed arguments passed in the command line.
     */
    private $_userPassedArgv = array();

    /*
     * Available commands created and passed in with aliases to call each command.
     */
    private $_commands = array();

    /*
     * These are options global to all commands for the application.
     */
    private $_applicationOptions = array(
        'quiet'   => array(
            'aliases'     => array(
                'quiet',
                'q'
            ),
            'description' => "Don't output any messages",
        ),
        'verbose' => array(
            'aliases'     => array(
                'verbose',
                'v'
            ),
            'description' => 'Output all application information',
        ),
    );

    public function __construct($applicationName = '', $argv = null)
    {
        if (!$argv) {
            $argv = $_SERVER['argv'];
        }

        $this->_applicationName = $applicationName;
        $this->_userPassedArgv = $argv;

        parent::__construct();
    }

    public function addCommand(BaseCommand $instance)
    {
        $instance->configure();
        $this->_commands[$instance->getName()] = $instance;
    }

    public function run()
    {
        $parsedArgv = $this->getParseInputArgv();

        $applicationOptions = $parsedArgv['application_options'];
        $parsedArgv = $parsedArgv['args'];

        $command = array_shift($parsedArgv);

        if ($command) {
            $this->_callApplication($command, $parsedArgv, $applicationOptions);
        }
        else {
            $this->_listCommands();
        }
    }

    public function getParseInputArgv(array $argv = array())
    {
        $paramsToParse = (!empty($argv) ? $argv : $this->_userPassedArgv);

        $applicationOptions = array();
        foreach ($paramsToParse as $index => $param) {
            if ($param == $_SERVER['SCRIPT_NAME']) {
                continue;
            }
            if (preg_match('#\A-#', $param)) {
                $applicationOptions[] = ltrim($param, '-');
                unset($paramsToParse[$index]);
            }
            else {
                break;
            }
        }

        $parser = new ArgumentParser($paramsToParse);

        return array(
            'application_options' => $applicationOptions,
            'args' => $parser->parseArgs(),
        );
    }

    private function _callApplication($applicationName, $commandParameters, $applicationParameters)
    {
        if (!isset($this->_commands[$applicationName])) {
            $this->_listCommands();
        }
        else {
            $command = $this->_commands[$applicationName];
            $command->setup($commandParameters, $applicationParameters);
            $command->configure();
            $command->verify();
            $command->run();
        }
    }

    private function _listCommands()
    {
        $options = "";
        foreach ($this->_applicationOptions as $name => $info) {
            $aliases = array();
            foreach ($info['aliases'] as $alias) {
                if (strlen($alias) === 1) {
                    $aliases[] = "-$alias";
                }
                else {
                    $aliases[] = "--$alias";
                }
            }

            $aliases = implode(', ', $aliases);
            $options .= "<info>\t$aliases\t\t{$info['description']}</info>\n";
        }

        $commands = "";
        foreach ($this->_commands as $name => $instance) {
            $description = $instance->getDescription();
            $commands .= "<info>\t$name\t\t$description</info>\n";

            $params = '';
            foreach ($instance->getDefinedParameters() as $parameter) {
                $names = array();
                if ($parameter->getShortName()) {
                    $names[] = '-' . $parameter->getShortName();
                }
                if ($parameter->getLongName()) {
                    $names[] = '--' . $parameter->getLongName();
                }
                $params .= "\t" . implode(', ', $names);

                if ($parameter->getDescription()) {
                    $params .= ": " . $parameter->getDescription();
                }
            }

            if ($params) {
                $commands .= "<info>\t\t\t$params</info>\n";
            }
        }

        // Build application and version information
        $applicationInformation = array();
        if ($this->_applicationName) {
            $applicationInformation[] = "$this->_applicationName";
        }
        if (!empty($applicationInformation)) {
            $applicationInformation = implode(' ', $applicationInformation) . "\n";
        }
        else {
            $applicationInformation = '';
        }

        $usage = <<<__USAGE__
$applicationInformation
<warning>Usage:</warning>
    [options] command [arguments]

<warning>Available Options:</warning>
    $options
<warning>Available Commands:</warning>
$commands

__USAGE__;

        $this->out($usage);
    }
}
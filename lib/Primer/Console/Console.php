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

    public function __construct($applicationName = '', $applicationVersion = '', $argv = null)
    {
        if (!$argv) {
            $argv = $_SERVER['argv'];
        }

        $this->_applicationName = $applicationName;
        $this->_applicationVersion = $applicationVersion;
        $this->_userPassedArgv = $argv;

        parent::__construct();
    }

    public function addCommand($command, Array $aliases)
    {
        if (is_string($command)) {
            $this->_commands[$command] = $aliases;
        }
        else {
            $this->_commands[get_class($command)] = $aliases;
        }
    }

    public function run()
    {
        $parsedArgv = $this->getParseInputArgv();

        $command = null;
        if (isset($parsedArgv[0])) {
            $command = $parsedArgv[0];
            unset($parsedArgv[0]);
        }

        $applicationOptions = array();
        foreach ($this->_applicationOptions as $option => $info) {
            foreach ($info['aliases'] as $param) {
                if (isset($parsedArgv[$param])) {
                    $applicationOptions[$param] = $parsedArgv[$param];
                    unset($parsedArgv[$param]);
                }
            }
        }

        $this->_callApplication($command, $parsedArgv, $applicationOptions);
    }

    public function getParseInputArgv(array $argv = array())
    {
        $paramsToParse = (!empty($argv) ? $argv : $this->_userPassedArgv);
        $parser = new ArgumentParser($paramsToParse);

        return $parser->parseArgs();
    }

    private function _callApplication($applicationName, $commandParameters, $applicationParameters)
    {
        $commandAvailable = false;
        foreach ($this->_commands as $className => $aliases) {
            if (in_array($applicationName, $aliases)) {
                $commandAvailable = true;
                break;
            }
        }

        if (!$commandAvailable) {
            $this->_listCommands();
        }
        else {
            $application = new $className();
            $application->setup($aliases, $commandParameters, $applicationParameters);
            $application->configure();
            $application->verify();
            $application->run();
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
        foreach ($this->_commands as $command => $aliases) {
            $command = new $command();
            $command->configure();
            $names = implode(', ', $aliases);
            $description = $command->getDescription();
            $commands .= "<info>\t$names\t\t$description</info>\n";

            $params = '';
            foreach ($command->getDefinedParameters() as $parameter) {
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
            $applicationInformation[] = "<info>$this->_applicationName</info>";
        }
        if ($this->_applicationVersion) {
            $applicationInformation[] = "version <warning>$this->_applicationVersion</warning>";
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
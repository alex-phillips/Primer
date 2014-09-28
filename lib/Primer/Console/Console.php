<?php
/**
 * Dispatcher
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console;

use Primer\Console\Input\ArgumentParser;

class Console extends ConsoleObject
{
    private $_applicationName;
    private $_applicationVersion;
    private $_userPassedArgv = array();
    private $_commands = array();
    private $_applicationOptions = array(
        'quiet' => array(
            'aliases' => array('quiet', 'q'),
            'description' => "Don't output any messages",
        ),
        'verbose' => array(
            'aliases' => array('verbose', 'v'),
            'description' => 'Output all application information',
        ),
    );

    static public function runScript($argv)
    {
        $dispatcher = new Dispatcher($argv);
        $dispatcher->dispatch();
    }

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

    public function addCommand(BaseCommand $command, Array $aliases)
    {
        $this->_commands[get_class($command)] = $aliases;
    }

    public function dispatch()
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
            $application->main();
        }
    }

    public function getParseInputArgv(array $argv = array())
    {
        $paramsToParse = (!empty($argv) ? $argv : $this->_userPassedArgv);
        $parser = new ArgumentParser($paramsToParse);

        return $parser->parseArgs();
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
            $options .= "\t$aliases\t\t{$info['description']}\n";
        }

        $commands = "";
        foreach ($this->_commands as $command => $aliases) {
            $command = new $command();
            $command->configure();
            $names = implode(', ', $aliases);
            $description = $command->getDescription();
            $commands .= "\t$names\t\t$description\n";

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
                $commands .= "\t\t\t$params\n";
            }
        }

        echo <<<__USAGE__
Usage:
    [options] command [arguments]

Available Options:
    $options
Available Commands:
$commands
__USAGE__;

    }
}
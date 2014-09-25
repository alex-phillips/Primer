<?php

namespace Primer\Console;

use Exception;

class Console
{
    const OPT_RETURN_INSTEAD_OF_EXIT = 'returnInsteadOfExit';
    const OPT_SLIENT = 'silent';
    const ERR_USAGE = -1;

    protected $name;
    protected $version;
    protected $commandMap = array();
    protected $usageCommands = array();
    protected $defaultCommand = null;
    protected $defaultCommandAlwaysRuns = false;
    protected $environment = array();
    /**
     * @var string The character linking a command flag to its argument. Default NULL (ie whitespace).
     */
    protected $argLinker = null;

    public function __construct($name= '', $version = '1.0', $opts = array())
    {
        $this->name = $name;
        $this->version = $version;
        $this->environment = $_ENV;
        $this->options = array_merge(array(
            self::OPT_RETURN_INSTEAD_OF_EXIT => false,
            self::OPT_SLIENT                 => false,
        ), $opts);
    }

    public static function create($name = '', $version = '', $opts = array())
    {
        return new Console($name, $version, $opts);
    }

    public function mergeEnvironment($env, $opts = array())
    {
        if (!is_array($env)) {
            throw new Exception("Array required.");
        }

        if (isset($opts['overwrite']) && $opts['overwrite']) {
            $this->environment = array_merge($this->environment, $env);
        }
        else {
            $this->environment = array_merge($env, $this->environment);
        }
        return $this;
    }

    public function getEnvironment($key = null)
    {
        if ($key) {
            if (!array_key_exists($key, $this->environment)) {
                return null;
            }

            return $this->environment[$key];
        }
        else {
            return $this->environment;
        }
    }

    public function setEnvironment($key, $value = null)
    {
        if (is_array($key)) {
            if ($value !== null) {
                throw new Exception("When calling setEnvironment() with an array, only 1 parameter is accepted.");
            }
            $this->environment = $key;
        }
        else {
            $this->environment[$key] = $value;
        }
        return $this;
    }

    public function hasEnvironment($key)
    {
        return array_key_exists($key, $this->environment);
    }

    public function addEnvironmentFlagWithExactlyOneArgument($key, $aliases = NULL, $opts = array())
    {
        if ($aliases === NULL) {
            $aliases = "--{$key}";
        }
        $opts = array_merge($opts, array('requiresArgument' => true));   // requiresArgument should always win
        $this->addCommand(new EnvironmentOption($key, $opts), $aliases);

        return $this;
    }

    public function addCommand($command, $aliases = array())
    {
        if (!($command instanceof CommandInterface)) throw new Exception("Command required.");

        if (!is_array($aliases)) {
            $aliases = array($aliases);
        }

        if (count($aliases) === 0) {
            throw new Exception("addCommand() requires at least one alias.");
        }

        foreach ($aliases as $alias) {
            if (isset($this->commandMap[$alias])) {
                throw new Exception("Command " . get_class($this->commandMap[$alias]) . " has already been registered for alias {$alias}.");
            }
            $this->commandMap[$alias] = $command;
        }
        $this->usageCommands[] = array('aliases' => $aliases, 'command' => $command);

        return $this;
    }

    public function addEnvironmentFlagSetsValue($key, $flagSetsValue, $aliases = NULL, $opts = array())
    {
        if ($aliases === NULL) {
            $aliases = "--{$key}";
        }
        $opts = array_merge($opts, array('requiresArgument' => false, 'noArgumentValue' => $flagSetsValue)); // these values always win
        $this->addCommand(new EnvironmentOption($key, $opts), $aliases);

        return $this;
    }

    public function setDefaultCommand($command, $opts = array())
    {
        if ($this->defaultCommand) {
            throw new Exception("A default command has already been registered.");
        }

        $this->defaultCommand = $command;
        $this->defaultCommandAlwaysRuns = (isset($opts['alwaysRuns']) && $opts['alwaysRuns']);

        return $this;
    }

    public function run($argv, $argc, $opts = array())
    {
        $commandNameRun = array_shift($argv);

        $result = 0;
        $commands = array();
        $previousCommand = NULL;

        // convert argv stack into processable list
        $cmd = NULL;
        $cmdToken = NULL;
        $args = array();
        $defaultCommandArguments = array();
        while (true) {
            $token = array_shift($argv);
            //print "processing '{$token}'\n";
            if ($token === null) {   // reached end
                if ($cmd) {  // push last command
                    $commands[] = array(
                        'command'   => $cmd,
                        'arguments' => $args,
                        'token'     => $cmdToken
                    );
                    $cmd = null;
                    $args = array();
                }
                if ($this->defaultCommand and (count($commands) === 0 or $this->defaultCommandAlwaysRuns)) {
                    //print "adding default command\n";
                    if (count($commands) >= 1) {
                        $args = $defaultCommandArguments;
                    }
                    $commands[] = array(
                        'command'   => $this->defaultCommand,
                        'arguments' => $args,
                        'token'     => '<default>'
                    );
                }
                break;
            }

            $nextCmd = $this->commandForToken($token);
            if ($nextCmd) {
                if ($cmd) {
                    $commands[] = array('command' => $cmd, 'arguments' => $args, 'token' => $cmdToken);
                }
                else {    // stash original set of arguments away for use with defaultCommand as needed
                    $defaultCommandArguments = $args;
                }
                $cmd = $nextCmd;
                $cmdToken = $token;
                $args = array();
            }
            else {
                $args[] = $token;
            }
        }

        if (count($commands) === 0) {
            return $this->usage();
        }

        // run commands
        $currentCommand = NULL;
        try {
            foreach ($commands as $key => $command) {
                $currentCommand = $command;
                //print "Calling " . get_class($command['command']) . "::run(" . join(', ', $command['arguments']) . ")";
                $cmdCallback = array($command['command'], 'run');
                if (!is_callable($cmdCallback)) throw new Exception("Not callable: " . var_export($cmdCallback, true));
                $result = call_user_func_array($cmdCallback, array($command['arguments'], $this));
                if (is_null($result)) throw new Exception("Command " . get_class($command['command']) . " returned NULL.");
                if ($result !== 0) break;
            }
        } catch (ArugumentException $e) {
            $this->options[self::OPT_SLIENT] || fwrite(STDERR, "Error processing {$currentCommand['token']}: {$e->getMessage()}\n");
            $result = -2;
        } catch (Exception $e) {
            $this->options[self::OPT_SLIENT] || fwrite(STDERR, get_class($e) . ": {$e->getMessage()}\n{$e->getTraceAsString()}\n");
            $result = -1;
        }

        if ($this->options['returnInsteadOfExit']) {
            return $result;
        }
        else {
            exit($result);
        }
    }

    protected final function commandForToken($token)
    {
        if (isset($this->commandMap[$token])) {
            return $this->commandMap[$token];
        }

        return NULL;
    }

    // returns the Command or NULL if not a command switch

    public function usage()
    {
        passthru('clear');
        $colorizer = new Colorizer();

        $markup = <<<__TEXT__
{$colorizer->getColoredString($this->name, 'green')} version {$colorizer->getColoredString($this->version, 'yellow')}

{$colorizer->getColoredString('Usage', 'yellow')}
    [options] command [arguments]


__TEXT__;

        print $markup;

        foreach ($this->usageCommands as $usageInfo) {
            print "  {$usageInfo['command']->getUsage($usageInfo['aliases'], $this->argLinker)}\n";
        }

        if ($this->options['returnInsteadOfExit']) {
            return self::ERR_USAGE;
        }
        else {
            exit(self::ERR_USAGE);
        }
    }
}
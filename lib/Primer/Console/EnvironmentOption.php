<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/24/14
 * Time: 6:19 PM
 */

namespace Primer\Console;

use Exception;
use Primer\Console\Exception\ArgumentException;

class EnvironmentOption extends BaseCommand
{
    protected $environmentKey;
    protected $requiresArgument;
    protected $allowsMultipleArguments;
    protected $noArgumentValue;
    protected $allowedValues;

    public function __construct($environmentKey, $opts = array())
    {
        $this->environmentKey = $environmentKey;
        $opts = array_merge(array(
            'requiresArgument'        => false,
            'allowsMultipleArguments' => false,
            'noArgumentValue'         => null,
            'allowedValues'           => null,
        ), $opts);
        $this->requiresArgument = $opts['requiresArgument'];
        $this->allowsMultipleArguments = $opts['allowsMultipleArguments'];
        $this->noArgumentValue = $opts['noArgumentValue'];
        $this->allowedValues = $opts['allowedValues'];
    }

    public function run($arguments, Console $cliController)
    {
        // argument checks
        if ($this->requiresArgument && count($arguments) === 0) {
            throw new ArgumentException("Argument required.");
        }
        if (!$this->allowsMultipleArguments && count($arguments) > 1) {
            throw new ArgumentException("Only one argument accepted.");
        }

        if (count($arguments) === 0 && $this->noArgumentValue) {
            $arguments = array($this->noArgumentValue);
        }

        if (!is_array($arguments)) throw new Exception("Arguments should be an array but wasn't. Internal fail.");
        if ($this->allowedValues) {
            $badArgs = array_diff($arguments, $this->allowedValues);
            if (count($badArgs) > 0) throw new ArgumentException("Invalid argument(s): " . join(', ', $badArgs));
        }

        // flatten argument to a single value as a convenience for working with the environment data later
        if (count($arguments) === 1) {
            $arguments = $arguments[0];
        }

        $cliController->setEnvironment($this->environmentKey, $arguments);

        return 0;
    }
}
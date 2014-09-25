<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/24/14
 * Time: 6:20 PM
 */

namespace Primer\Console;

interface CommandInterface
{
    const ARG_NONE = 'none';
    const ARG_OPTIONAL = 'optional';
    const ARG_REQUIRED = 'required';

    public function run($arguments, Console $cliController);

    public function getUsage($aliases, $argLinker);

    public function getDescription($aliases, $argLinker);

    public function getArgumentType();

    public function getAllowsMultipleUse();
}
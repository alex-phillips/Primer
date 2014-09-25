<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/24/14
 * Time: 7:45 PM
 */

namespace Primer\Console;

class UpCommand extends BaseCommand
{
    public function run($arguments, Console $cliController)
    {
        unlink(APP_ROOT . '/Config/down');
        return "Server has been brought up\n";
    }

    public function getDescription($aliases, $argLinker)
    {
        return "Bring back the server from maintenance mode";
    }
}
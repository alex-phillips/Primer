<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/24/14
 * Time: 6:33 PM
 */

namespace Primer\Console;

class DownCommand extends BaseCommand
{
    public function run($arguments, Console $cliController)
    {
        touch(APP_ROOT . '/Config/down');
        return "Server has been brought down\n";
    }

    public function getDescription($aliases, $argLinker)
    {
        return "Bring down the application into maintenance mode";
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/24/14
 * Time: 6:33 PM
 */

namespace Primer\Console;

use Primer\Console\Input\DefinedInput;

class DownCommand extends BaseCommand
{
    public function main()
    {
        if (touch(APP_ROOT . '/Config/down')) {
            $this->out("Server has been brought down.");
        }
        else {
            $this->out("There was a problem putting the server into maintenance mode. Please check file permissions.");
        }
    }

//    public function getDescription($aliases, $argLinker)
//    {
//        return "Bring down the application into maintenance mode";
//    }
}
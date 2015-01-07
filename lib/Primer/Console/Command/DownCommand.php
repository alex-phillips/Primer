<?php
/**
 * DownCommand
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 */

namespace Primer\Console\Command;

use Primer\Console\Input\DefinedInput;

class DownCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('down');
        $this->setDescription("Bring the server down for maintenance mode");
        $this->addOption('when', array('w'), null, 'Specify time in seconds to delay until bringing down the application');

        $this->addArgument('when');
    }

    public function run()
    {
        if (touch(APP_ROOT . '/Config/down')) {
            $this->out("Server has been brought down.");
        }
        else {
            $this->out("There was a problem putting the server into maintenance mode. Please check file permissions.");
        }
    }
}
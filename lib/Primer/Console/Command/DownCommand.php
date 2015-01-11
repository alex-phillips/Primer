<?php
/**
 * DownCommand
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 */

namespace Primer\Console\Command;

class DownCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('down');
        $this->setDescription("Bring the application down for maintenance");
    }

    public function run()
    {
        if (touch(APP_ROOT . '/Config/down')) {
            $this->out("<warning>Application has been brought down for maintenance</warning>");
        }
        else {
            $this->out("<error>There was a problem putting the application into maintenance mode. Please check file permissions.</error>");
        }
    }
}
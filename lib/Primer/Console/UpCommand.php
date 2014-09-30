<?php
/**
 * UpCommand
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 * @date 9/24/14
 * @time 7:45 PM
 */

namespace Primer\Console;

class UpCommand extends BaseCommand
{
    public function configure()
    {
        $this->setDescription("Bring the server out of maintenance mode");
    }

    public function run()
    {
        if (unlink(APP_ROOT . '/Config/down')) {
            $this->out("Server has been brought up.");
        }
        else {
            $this->out("There was a problem bringing the server out of maintenance mode. Please check file permissions.");
        }
    }
}
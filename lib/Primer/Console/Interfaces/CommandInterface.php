<?php
/**
 * ApplicationInterface
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console\Interfaces;

interface CommandInterface
{
    public function configure();

    public function run();
}
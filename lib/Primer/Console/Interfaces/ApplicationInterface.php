<?php
/**
 * ApplicationInterface
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console\Interfaces;

interface ApplicationInterface
{
    public function configure();
    public function main();
}
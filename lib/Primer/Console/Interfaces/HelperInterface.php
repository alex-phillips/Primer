<?php
/**
 * HelperInterface
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console\Interfaces;

use Primer\Console\Output\Writer;

interface HelperInterface
{
    public function render(Writer $output);
}
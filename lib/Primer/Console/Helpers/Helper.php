<?php
/**
 * Helper
 *
 * @author Piotr Olaszewski
 */
namespace Primer\Console\Helpers;

use Primer\Console\Exception\HelperException;

class Helper
{
    public static function loadHelper($helperName)
    {
        $helperClassName = __NAMESPACE__ . '\\' . $helperName;
        if (class_exists($helperClassName)) {
            return new $helperClassName();
        }
        throw new HelperException("Couldn't load helper [$helperName], please check is exists.");
    }
}
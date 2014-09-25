<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 9/24/14
 * Time: 6:20 PM
 */

namespace Primer\Console;

abstract class BaseCommand implements CommandInterface
{
    public function getAllowsMultipleUse()
    {
        return false;
    }

    public function getUsage($aliases, $argLinker)
    {
        // calculate arg linker string
        switch ($this->getArgumentType()) {
            case CommandInterface::ARG_NONE:
                $argLinker = null;
                break;
            default:
                $argLinker = "{$argLinker}<arg>";
                break;
        }
        $cmd = null;
        foreach ($aliases as $alias) {
            if ($cmd) {
                $cmd .= " / ";
            }
            $cmd .= "{$alias}{$argLinker}";
        }

        $description = $this->getDescription($aliases, $argLinker);
        if ($description) {
            $cmd .= "\t\t{$description}";
        }

        return $cmd;
    }

    public function getArgumentType()
    {
        return CommandInterface::ARG_NONE;
    }

    public function getDescription($aliases, $argLinker)
    {
        return null;
    }
}
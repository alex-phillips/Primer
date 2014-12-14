<?php

namespace Primer\Routing;

class RouteCollection extends \SplObjectStorage
{
    /**
     * Fetch all routers stored on this collection of router
     * and return it.
     *
     * @return array
     */
    public function all()
    {
        $_tmp = array();
        foreach ($this as $objectValue) {
            $_tmp[] = $objectValue;
        }

        return $_tmp;
    }
}
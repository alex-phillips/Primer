<?php

/**
 * Class SessionContainer
 *
 * Modified ParameterContainer that passes the parameters by reference. This allows
 * the $_SESSION global to be modified as the class modifies the parameters inside
 * of the class.
 *
 * @author: Alex Phillips <exonintrendo@gmail.com>
 * @date: 9/12/14
 * @time: 10:26 PM
 */

namespace Primer\Session;

use Primer\Utility\ParameterContainer;

class SessionContainer extends ParameterContainer
{
    public function __construct(&$parameters)
    {
        $this->_parameters = & $parameters;
    }
}
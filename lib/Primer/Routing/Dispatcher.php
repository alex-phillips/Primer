<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/11/14
 * Time: 5:15 PM
 */

namespace Primer\Routing;

use Primer\Core\Object;
use Primer\Http\Request;

class Dispatcher extends Object
{
    private $_request;

    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    public function dispatch()
    {
        try {
            $reflection = new \ReflectionClass($this->getControllerName($this->_request->params['controller']));
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return false;
            }

            $controller = $reflection->newInstance();

            return $controller->invokeAction($this->_request);
        }
        catch (\Exception $e) {
            $test = 1;
        }
    }
}
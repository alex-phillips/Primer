<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/11/14
 * Time: 5:15 PM
 */

namespace Primer\Routing;

use Primer\Core\Application;
use Primer\Core\Object;
use Primer\Controller\Exception\MissingControllerException;
use Primer\Http\Request;
use Primer\Http\Response;

class Dispatcher extends Object
{
    private $_app;
    private $_request;
    private $_response;
    private $_router;

    public function __construct(Request $request, Response $response, Router $router)
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_router = $router;
    }

    public function dispatch()
    {
        $route = $this->_router->matchRequest($this->_request->url, $this->_request->requestMethod);
        $this->_request->addParams($route->getParams());

        try {
            $reflection = new \ReflectionClass($this->getControllerName($this->_request->params['controller']));
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return false;
            }

            $controller = $reflection->newInstance($this->_request, $this->_response);

            return $controller->invokeAction($this->_request);
        }
        catch (\Exception $e) {
            throw new MissingControllerException($this->_request->params['controller']);
        }
    }

    public function setApp(Application $app)
    {
        $this->_app = $app;
    }
}
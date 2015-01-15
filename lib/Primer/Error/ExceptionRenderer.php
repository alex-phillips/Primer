<?php
/**
 * @author Alex Phillips <aphillips@cbcnewmedia.com>
 * Date: 12/15/14
 * Time: 10:44 AM
 */

namespace Primer\Error;

use Exception;
use Primer\Controller\Controller;
use Primer\Core\Application;
use Primer\Utility\Inflector;
use PDOException;
use Primer\View\Exception\MissingViewException;

class ExceptionRenderer
{
    /**
     * Controller instance.
     *
     * @var Controller
     */
    public $controller = null;

    /**
     * template to render for CakeException
     *
     * @var string
     */
    public $template = '';

    /**
     * The method corresponding to the Exception this object is for.
     *
     * @var string
     */
    public $method = '';

    /**
     * The exception being handled.
     *
     * @var Exception
     */
    public $error = null;

    /**
     * Creates the controller to perform rendering on the error response.
     * If the error is a CakeException it will be converted to either a 400 or a 500
     * code error depending on the code used to construct the error.
     *
     * @param Exception $exception Exception
     */
    public function __construct(Exception $exception, Application $app)
    {
        $this->_app = $app;
        $this->controller = new Controller($app['request'], $app['response']);

        if (method_exists($this->controller, 'appError')) {
            $this->controller->appError($exception);
            return;
        }

        $exceptionName = explode('\\', str_replace('Exception', '', get_class($exception)));
        $exceptionName = array_pop($exceptionName);

        $method = $template = Inflector::variable(
            $exceptionName
        );

        $code = $exception->getCode();

        $methodExists = method_exists($this, $method);

        if ($exception instanceof PrimerException && !$methodExists) {
            $method = '_primerError';
            if (empty($template) || $template === 'internalError') {
                $template = 'error500';
            }
        }
        else if ($exception instanceof PDOException) {
            $method = 'pdoError';
            $template = 'pdo_error';
            $code = 500;
        }
        else if (!$methodExists) {
            $method = 'error500';
            if ($code >= 400 && $code < 500) {
                $method = 'error400';
            }
        }

        $isNotDebug = !$this->_app['config']['app.debug'];
        if ($isNotDebug && $method === '_primerError') {
            $method = 'error400';
        }

        if ($isNotDebug && $code == 500) {
            $method = 'error500';
        }

        $this->template = $template;
        $this->method = $method;
        $this->error = $exception;
    }

    /**
     * Get the controller instance to handle the exception.
     * Override this method in subclasses to customize the controller used.
     * This method returns the built in `CakeErrorController` normally, or if an error is repeated
     * a bare controller will be used.
     *
     * @param Exception $exception The exception to get a controller for.
     *
     * @return Controller
     */
    protected function _getController($exception)
    {
        if (!$request = Router::getRequest(true)) {
            $request = new CakeRequest();
        }
        $response = new CakeResponse();

        if (method_exists($exception, 'responseHeader')) {
            $response->header($exception->responseHeader());
        }

        if (class_exists('AppController')) {
            try {
                $controller = new CakeErrorController($request, $response);
                $controller->startupProcess();
            } catch (Exception $e) {
                if (!empty($controller)
                    && $controller->Components->enabled(
                        'RequestHandler'
                    )
                ) {
                    $controller->RequestHandler->startup($controller);
                }
            }
        }
        if (empty($controller)) {
            $controller = new Controller($request, $response);
            $controller->viewPath = 'Errors';
        }

        return $controller;
    }

    /**
     * Renders the response for the exception.
     *
     * @return void
     */
    public function render()
    {
        if ($this->method) {
            call_user_func_array(
                array($this, $this->method), array($this->error)
            );
        }
    }

    /**
     * Generic handler for the internal framework errors CakePHP can generate.
     *
     * @param CakeException $error The exception to render.
     *
     * @return void
     */
    protected function _primerError(PrimerException $error)
    {
        $url = $this->controller->request->here();
        $code = ($error->getCode() >= 400 && $error->getCode() < 506)
            ? $error->getCode() : 500;
        $this->controller->response->setStatusCode($code);
        $this->controller->set(
            array(
                'code'       => $code,
                'title'      => $error->getMessage(),
                'name'       => $error->getMessage(),
                'message'    => $error->getMessage(),
                'url'        => $url,
                'error'      => $error,
                '_serialize' => array('code', 'name', 'message', 'url')
            )
        );
        $this->controller->set($error->getAttributes());
        $this->_outputMessage($this->template);
    }

    /**
     * Convenience method to display a 400 series page.
     *
     * @param Exception $error The exception to render.
     *
     * @return void
     */
    public function error400($error)
    {
        $message = $error->getMessage();
        if (!$this->_app['config']['app.debug'] && $error instanceof PrimerException) {
            $message = 'Not Found';
        }
        $url = $this->controller->request->here();
        $this->controller->response->setStatusCode($error->getCode());
        $this->controller->set(
            array(
                'title'      => $message,
                'name'       => $message,
                'message'    => $message,
                'url'        => $url,
                'error'      => $error,
                '_serialize' => array('name', 'message', 'url')
            )
        );
        $this->_outputMessage('error400');
    }

    /**
     * Convenience method to display a 500 page.
     *
     * @param Exception $error The exception to render.
     *
     * @return void
     */
    public function error500($error)
    {
        $message = $error->getMessage();
        if (!$this->_app['config']['app.debug']) {
            $message = 'An Internal Error Has Occurred.';
        }
        $url = $this->controller->request->here();
        $code = ($error->getCode() > 500 && $error->getCode() < 506)
            ? $error->getCode() : 500;
        $this->controller->response->setStatusCode($code);
        $this->controller->set(
            array(
                'title'      => $message,
                'name'       => $message,
                'message'    => $message,
                'url'        => $url,
                'error'      => $error,
                '_serialize' => array('name', 'message', 'url')
            )
        );
        $this->_outputMessage('error500');
    }

    /**
     * Convenience method to display a PDOException.
     *
     * @param PDOException $error The exception to render.
     *
     * @return void
     */
    public function pdoError(PDOException $error)
    {
        $url = $this->controller->request->here();
        $code = 500;
        $this->controller->response->setStatusCode($code);
        $this->controller->set(
            array(
                'code'       => $code,
                'title'      => $error->getMessage(),
                'name'       => $error->getMessage(),
                'message'    => $error->getMessage(),
                'url'        => $url,
                'error'      => $error,
                '_serialize' => array('code', 'name', 'message', 'url', 'error')
            )
        );
        $this->_outputMessage($this->template);
    }

    /**
     * Generate the response using the controller object.
     *
     * @param string $template The template to render.
     *
     * @return void
     */
    protected function _outputMessage($template)
    {
        $template = "errors.$template";
        try {
            $layout = $this->_app['config']['error.layout'];
            $this->controller->response->setContent($this->controller->render($template, $layout));
            $this->controller->afterFilter();
            $this->controller->response->send();
        } catch (MissingViewException $e) {
            $attributes = $e->getAttributes();
            if (isset($attributes['file']) && strpos($attributes['file'], 'error500') !== false) {
                $this->_outputMessageSafe('error500');
            }
            else {
                $this->_outputMessage('error500');
            }
        } catch (Exception $e) {
            $this->_outputMessageSafe('error500');
        }
    }
}

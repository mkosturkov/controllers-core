<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\HttpMiddlerwareInterface;
use \Tys\Controllers\Exceptions\ResponseNotSetException;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

/**
 * Controller for use in HTTP enviornment
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class HttpController extends Controller
{

    /**
     * A PSR-7 compliant request object
     * 
     * @var RequestInterface
     */
    private $request;
    
    /**
     * A PSR-7 compliant response object
     * 
     * @var ResponseInterface
     */
    private $response;
    
    protected $middlewareRunMethod = 'httpRun';

    /**
     * @param ContainerInterface $dic A DIC implementation
     * @param RequestInterface $request A PSR-7 compliant request implementation
     */
    public function __construct(ContainerInterface $dic, RequestInterface $request)
    {
        parent::__construct($dic);
        $this->request = $request;
    }
    
    /**
     * Append http middleware to the end of the middleware queue
     * 
     * @param HttpMiddlerwareInterface $middleware
     * @return self
     */
    public function appendHttpMiddleware(HttpMiddlerwareInterface $middleware)
    {
        $this->queue->appendItem($middleware);
        return $this;
    }
    
    /**
     * Prepend http middleware to the beggining of the middleware queue
     * 
     * @param HttpMiddlerwareInterface $middleware
     * @return self
     */
    public function prependHttpMiddleware(HttpMiddlerwareInterface $middleware)
    {
        $this->queue->prependItem($middleware);
        return $this;
    }
    
    /**
     * Returns an a PSR-7 compliant request object
     * 
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Set a response to return to the client
     * 
     * @param ResponseInterface $response A PSR-7 compliant response object
     * @return self
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }
    
    /**
     * Returns a response that will be sent back to the client
     * at the end of the controller's execution
     * @return ResponseInterface
     * @throws ResponseNotSetException When a response had not been set
     */
    public function getResponse()
    {
        if (!isset ($this->response)) {
            throw new ResponseNotSetException('Response has not been set!');
        }
        return $this->response;
    }
}

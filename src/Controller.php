<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\MiddlewareInterface;
use \Interop\Container\ContainerInterface;

/**
 * Base class for application controllers.
 * Provides functionality for running middleware.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class Controller
{

    /**
     * Queue to hold middleware
     * 
     * @var UseOnceQueue
     */
    private $queue;
    
    /**
     * Dependancy injection container
     * 
     * @var ContainerInterface
     */
    private $dic;

    /**
     * Holds the return value
     * of the last run middlware
     * 
     * @var mixed
     */
    private $lastReturnValue;
    
    /**
     * Flag to indicate that the stop method
     * has been called
     * 
     * @var bool
     */
    private $stopFlag = false;

    /**
     * @param ContainerInterface $dic A Dependancy Injection Container
     */
    public function __construct(ContainerInterface $dic)
    {
        $this->dic = $dic;
        $this->queue = new UseOnceQueue();
    }
    
    /**
     * Prepend a callback to the execution queue
     * 
     * @param \callable $callback
     * @return \Tys\Controllers\Controller Returns self
     */
    public function prependCallback(callable $callback)
    {
        $this->queue->prependItem($callback);
        return $this;
    }
    
    /**
     * Append a callback to the execution queue
     * 
     * @param \callable $callback
     * @return \Tys\Controllers\Controller Returns self
     */
    public function appendCallback(callable $callback)
    {
        $this->queue->appendItem($callback);
        return $this;
    }
    
    /**
     * Prepend middleware to execution queue
     * 
     * @param MiddlewareInterface $middleware
     * @return Controller
     */
    public function prependMiddleware(MiddlewareInterface $middleware)
    {
        return $this->prependCallback([$middleware, 'run']);
    }
    
    /**
     * Append middleware to exection queue
     * 
     * @param MiddlewareInterface $middleware
     * @return Controller
     */
    public function appendMiddleware(MiddlewareInterface $middleware)
    {
        return $this->appendCallback([$middleware, 'run']);
    }
    
    /**
     * Run all the middleware available
     */
    public function run()
    {
        while (!$this->stopFlag && $this->queue->hasNext()) {
            $callback = $this->queue->getNextItem();
            $this->lastReturnValue = $callback($this);
        }
    }
    
    /**
     * Returns the return value of the
     * last executed middleware
     * 
     * @return mixed
     */
    public function getLastReturnValue()
    {
        return $this->lastReturnValue;
    }
    
    /**
     * Stop the execution of the middleware
     */
    public function stop()
    {
        $this->stopFlag = true;
    }
    
    /**
     * Returns a Dependancy Injection Container
     * 
     * @return ContainerInterface
     */
    public function getDIC()
    {
        return $this->dic;
    }

}

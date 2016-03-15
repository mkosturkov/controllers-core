<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\MiddlewareInterface;
use \Tys\Controllers\Exceptions\AlreadyRunningException;
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
     * Exception handlers map
     * 
     * @var array
     */
    private $exceptionHandlers = [];
    
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
     * Flag to indicate wether the run method had been called
     * and hasn't finished
     * 
     * @var bool
     */
    private $runningFlag = false;

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
     * Returns true is the run method had been called
     * and has not yet completed, false otherwise.
     * 
     * @return bool
     */
    public function isRunning()
    {
        return $this->runningFlag;
    }
    
    /**
     * Run all the middleware available
     */
    public function run()
    {
        if ($this->isRunning()) {
            throw new AlreadyRunningException('The controller is currently running!');
        }
        $this->runningFlag = true;
        try {
            while (!$this->stopFlag && $this->queue->hasNext()) {
                $callback = $this->queue->getNextItem();
                $this->lastReturnValue = $callback($this);
            }
        } catch (\Exception $ex) {
            $handled = false;
            foreach ($this->exceptionHandlers as $exceptionName => $handler) {
                if (is_a($ex, $exceptionName)) {
                    $handler($ex, $this);
                    $handled = true;
                    break;
                }
            }
            if (!$handled) {
                throw $ex;
            }
        }
        $this->runningFlag = false;
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
    
    /**
     * Set exception handler function
     * 
     * @param strin $exceptionName The class name of the exception and its descendants to handle
     * @param callable $handler The function
     * @return \Tys\Controllers\Controller Returns self
     */
    public function setExceptionHandlerCallback($exceptionName, callable $handler)
    {
        $this->exceptionHandlers[$exceptionName] = $handler;
        return $this;
    }

}

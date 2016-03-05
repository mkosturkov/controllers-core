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
    protected $dic;


    /**
     * Holds the return value
     * of the last run middlware
     * 
     * @var mixed
     */
    protected $lastReturnValue;
    
    /**
     * Flag to indicate that the stop method
     * has been called
     * 
     * @var bool
     */
    protected $stopFlag = false;
    
    /**
     * @param ContainerInterface $dic A Dependancy Injection Container
     */
    public function __construct(ContainerInterface $dic)
    {
        $this->dic = $dic;
        $this->queue = new UseOnceQueue();
    }
    
    /**
     * Prepend middleware to execution queue
     * 
     * @param MiddlewareInterface $middleware
     * @return Controller
     */
    public function prependMiddleware(MiddlewareInterface $middleware)
    {
        $this->queue->prependItem($middleware);
        return $this;
    }
    
    /**
     * Append middleware to exection queue
     * 
     * @param MiddlewareInterface $middleware
     * @return Controller
     */
    public function appendMiddleware(MiddlewareInterface $middleware)
    {
        $this->queue->appendItem($middleware);
        return $this;
    }
    
    /**
     * Run all the middleware available
     */
    public function run()
    {
        while (!$this->stopFlag && $this->queue->hasNext()) {
            $this->lastReturnValue = $this->queue->getNextItem()->run($this);
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

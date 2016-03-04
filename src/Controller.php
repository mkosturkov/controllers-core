<?php

namespace Tys\Controllers;

use \Interop\Container\ContainerInterface;

/**
 * Base class for application controllers.
 * Provides functionality for running middleware.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
abstract class Controller
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
     * Prepend middleware to execution queue
     * 
     * @param mixed $middleware
     * @return \Tys\Controllers\Controller
     */
    protected function prependMiddleware($middleware)
    {
        $this->queue->prependItem($middleware);
        return $this;
    }
    
    /**
     * Append middleware to exection queue
     * 
     * @param mixed $middleware
     * @return \Tys\Controllers\Controller
     */
    protected function appendMiddleware($middleware)
    {
        $this->queue->appendItem($middleware);
        return $this;
    }
    
    /**
     * @param ContainerInterface $dic A Dependancy Injection Container
     */
    public function __construct(ContainerInterface $dic)
    {
        $this->dic = $dic;
        $this->queue = new UseOnceQueue();
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

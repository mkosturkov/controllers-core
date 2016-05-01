<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\Middleware;
use \Tys\Controllers\Exceptions\AlreadyRunningException;

/**
 * Base class for application controllers.
 * Provides functionality for running middleware.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class Controller
{

   private $queue;
   
   private $queueModfier;
    
   private $finalQueue;
   
   private $finalQueueModifier;
    
   private $exceptionHandlers = [];
    
   private $lastReturnValue;
    
   private $stopFlag = false;
    
   private $runningFlag = false;

    public function __construct()
    {
        $this->queue = new MiddlewareQueue();
        $this->finalQueue = new MiddlewareQueue();
        $this->queueModfier = new MiddlewareQueueModifier($this->queue);
        $this->finalQueueModifier = new MiddlewareQueueModifier($this->finalQueue);
    }
    
    /**
     * 
     * @return MiddlewareQueueModifier
     */
    public function getQueueModifier()
    {
        return $this->queueModfier;
    }
    
    /**
     * 
     * @return MiddlewareQueueModifier
     */
    public function getFinalQueueModifier()
    {
        return $this->finalQueueModifier;
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
        
        while (!$this->stopFlag && $this->queue->hasNext()) {
            try {
                $middleware = $this->queue->getNext();
                $this->lastReturnValue = $middleware->run($this);
            } catch (\Exception $ex) {
                $this->stop();
                $handled = false;
                foreach ($this->exceptionHandlers as $exceptionType => $handler) {
                    if (is_a($ex, $exceptionType)) {
                        $handler($ex, $this);
                        $handled = true;
                        break;
                    }
                }
            }
        }
        
        while ($this->finalQueue->hasNext()) {
            $middleware = $this->finalQueue->getNext();
            $middleware->run($this);
        }
        
        if (isset ($ex) && !$handled) {
            throw $ex;
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
     * Continue execution of middleware
     */
    public function undoStop()
    {
        $this->stopFlag = false;
    }
    
    /**
     * Check wether the middleware execution had been stopped
     * 
     * @return bool
     */
    public function isStopped()
    {
        return $this->stopFlag;
    }
    
    /**
     * Set exception handler function
     * 
     * @param string $exceptionName The class name of the exception and its descendants to handle
     * @param callable $handler The function
     * @return \Tys\Controllers\Controller Returns self
     */
    public function setExceptionHandlerCallback($exceptionName, callable $handler)
    {
        $this->exceptionHandlers[$exceptionName] = $handler;
        return $this;
    }

}

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
    
   private $exceptionHandlers;
    
   private $lastReturnValue;
    
   private $stopFlag = false;
    
   private $runningFlag = false;

    public function __construct()
    {
        $this->queue = new MiddlewareQueue();
        $this->finalQueue = new MiddlewareQueue();
        $this->queueModfier = new MiddlewareQueueModifier($this->queue);
        $this->finalQueueModifier = new MiddlewareQueueModifier($this->finalQueue);
        $this->exceptionHandlers = new ExceptionHandlersCollection();
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
     * 
     * @return ExceptionHandlersCollection
     */
    public function getExceptionHandlersCollection()
    {
        return $this->exceptionHandlers;
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
        
        $queueRunResult = $this->tryQueueRun();
        $this->runFinalQueue();
        
        if (is_array($queueRunResult) && !$queueRunResult['handled']) {
            throw $queueRunResult['exception'];
        }
        $this->runningFlag = false;
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
     * Returns the return value of the
     * last executed middleware
     * 
     * @return mixed
     */
    public function getLastReturnValue()
    {
        return $this->lastReturnValue;
    }
    
    private function tryQueueRun()
    {
        while (!$this->stopFlag && $this->queue->hasNext()) {
            try {
                $middleware = $this->queue->getNext();
                $this->lastReturnValue = $middleware->run($this);
            } catch (\Exception $ex) {
                $this->stop();
                $handled = $this->handleException($ex);
            }
        }
        return isset ($ex) ? ['exception' => $ex, 'handled' => $handled] : true;
    }
    
    private function handleException(\Exception $exception)
    {
        if (($handler = $this->exceptionHandlers->getHandlerForException($exception))) {
            $handler->handle($this, $exception);
            return true;
        }
        return false;
    }
    
    private function runFinalQueue()
    {
        while ($this->finalQueue->hasNext()) {
            $middleware = $this->finalQueue->getNext();
            $middleware->run($this);
        }
    }

}

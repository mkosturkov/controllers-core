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

   private $startQueue;
   
   private $startQueueModfier;
    
   private $finalQueue;
   
   private $finalQueueModifier;
    
   private $exceptionHandlers;
   
   private $data;
    
   private $stopFlag = false;
    
   private $runningFlag = false;

    public function __construct()
    {
        $this->startQueue = new MiddlewareQueue();
        $this->finalQueue = new MiddlewareQueue();
        $this->startQueueModfier = new MiddlewareQueueModifier($this->startQueue);
        $this->finalQueueModifier = new MiddlewareQueueModifier($this->finalQueue);
        $this->exceptionHandlers = new ExceptionHandlersCollection();
    }
    
    /**
     * 
     * @return MiddlewareQueueModifier
     */
    public function getStartQueueModifier()
    {
        return $this->startQueueModfier;
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
     * Set arbitrary data to share with other middleware
     * 
     * @param mixed $result
     * @return this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Returns the last data set with Controller::setData()
     * 
     * @see Controller::setData()
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
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
    
    private function tryQueueRun()
    {
        while (!$this->stopFlag && $this->startQueue->hasNext()) {
            try {
                $this->runNextMiddleware($this->startQueue);
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
            $this->runNextMiddleware($this->finalQueue);
        }
    }
    
    private function runNextMiddleware(MiddlewareQueue $queue)
    {
        $middleware = $queue->getNext();
        $middleware->run($this);
    }

}

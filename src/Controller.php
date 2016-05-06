<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\Middleware;
use \Tys\Controllers\Exceptions\AlreadyRunningException;

/**
 * The class is responsible for seqeuntially
 * running the middleware in the middleware queues.
 * It holds two of them - main and final.
 * The main queue is run first. Exceptions thrown from
 * middleware in the main queue are cought and can be handled
 * if appropriate exception handler had been provided.
 * If no appropriate handler is found, the exception will be
 * rethrown after the final queue has executed.
 * When an exception is cought from the main queue
 * execution of the queue is stopped. It may be resumed
 * by the handler.
 * After execution of the main queue has finished,
 * either by exception or because all middleware had been run,
 * middleware in the final queue is executed. If any exceptions are thrown
 * during that time, they won't be handled in any way by the Controller object.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class Controller
{

   private $mainQueue;
   
   private $mainQueueModfier;
    
   private $finalQueue;
   
   private $finalQueueModifier;
    
   private $exceptionHandlers;
   
   private $data;
    
   private $stopFlag = false;
    
   private $runningFlag = false;

    public function __construct()
    {
        $this->mainQueue = new MiddlewareQueue();
        $this->finalQueue = new MiddlewareQueue();
        $this->mainQueueModfier = new MiddlewareQueueModifier($this->mainQueue);
        $this->finalQueueModifier = new MiddlewareQueueModifier($this->finalQueue);
        $this->exceptionHandlers = new ExceptionHandlersCollection();
    }
    
    /**
     * Returns a modifier for the main middleware queue.
     *  
     * @return MiddlewareQueueModifier
     */
    public function getMainQueueModifier()
    {
        return $this->mainQueueModfier;
    }
    
    /**
     * Returns a modifier for the final middleware queue.
     * 
     * @return MiddlewareQueueModifier
     */
    public function getFinalQueueModifier()
    {
        return $this->finalQueueModifier;
    }
    
    /**
     * Returns the exceptions handlers collection.
     * 
     * @return ExceptionHandlersCollection
     */
    public function getExceptionHandlersCollection()
    {
        return $this->exceptionHandlers;
    }
    
    /**
     * Set arbitrary data to share with other middleware
     * 
     * @param mixed $data
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
     * Returns true if the run method had been called
     * and has not yet completed, false otherwise.
     * 
     * @see Controller::run()
     * @return bool
     */
    public function isRunning()
    {
        return $this->runningFlag;
    }
    
    /**
     * Stop further execution of middleware
     * in the main queue
     */
    public function stop()
    {
        $this->stopFlag = true;
    }
    
    /**
     * Continue execution of middleware
     * in the main queue
     */
    public function undoStop()
    {
        $this->stopFlag = false;
    }
    
    /**
     * Check wether the middleware execution
     * in the main queue had been stopped
     * 
     * @return bool
     */
    public function isStopped()
    {
        return $this->stopFlag;
    }
    
    /**
     * Run all the middleware available
     * 
     * @throws AlreadyRunningException When the method is called before it has finished executing
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
    
    private function tryQueueRun()
    {
        while (!$this->stopFlag && $this->mainQueue->hasNext()) {
            try {
                $this->runNextMiddleware($this->mainQueue);
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

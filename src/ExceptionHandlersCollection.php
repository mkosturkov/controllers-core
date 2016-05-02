<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\ExceptionHandler;

/**
 * Holds an ordered collection of ExceptionHandler instances.
 * 
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ExceptionHandlersCollection
{
    private $handlersMap = [];
    
    /**
     * Adds and ExceptionHandler to the collection.
     * If an ExceptionHandler handling the same exception has already been
     * added, the current one is being ignored.
     * 
     * @param ExceptionHandler $handler
     * @return this
     * @see ExceptionHandler null if there was no suitable handler
     */
    public function add(ExceptionHandler $handler)
    {
        $handledException = $handler->getHandledExceptionName();
        if (!isset ($this->handlersMap[$handledException])) {
            $this->handlersMap[$handledException] = $handler;
        }
        return $this;
    }
    
    /**
     * Returns the first found handler able to handle the passed exception.
     * 
     * @param \Exception $exception
     * @return ExceptionHandler
     * @see ExceptionHandler
     */
    public function getHandlerForException(\Exception $exception)
    {
        foreach ($this->handlersMap as $exceptionName => $handler) {
            if (is_a($exception, $exceptionName)) {
                return $handler;
            }
        }
    }
}

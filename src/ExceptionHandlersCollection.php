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
     * Adds an ExceptionHandler to the collection.
     * If an ExceptionHandler handling the same exception has already been
     * added, the current one is being ignored.
     * Order in which the handlers are added is preserved.
     * 
     * @param ExceptionHandler $handler
     * @return this
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
     * Returns the first added handler that can handle exceptions
     * that are instances of the class of the exception passed as an argument
     * or instances of any of its parents.
     * 
     * @see ExceptionHandlersCollection::add()
     * @param \Exception $exception
     * @return ExceptionHandler
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

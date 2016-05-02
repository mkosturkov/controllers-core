<?php

namespace Tys\Controllers\Contracts;

use \Tys\Controllers\Controller;

/**
 * Interface to implement for objects used as exception handlers
 * 
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
interface ExceptionHandler
{
    /**
     * Returns the class name of the exception that this handler handles
     * 
     * @return string
     */
    public function getHandledExceptionName();
    
    /**
     * Handles the defined by ExceptionHandler::getHandledExceptionName() exception.
     * 
     * @see ExceptionHandler::getHandledExceptionName()
     * @param Controller $controller The currently running conctroller
     * @param mixed $exception The exception thrown
     */
    public function handle(Controller $controller, $exception);
}

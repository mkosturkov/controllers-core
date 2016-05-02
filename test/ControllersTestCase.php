<?php

use \Tys\Controllers\Contracts\Middleware;
use \Tys\Controllers\Contracts\ExceptionHandler;

/**
 * Base for controllers test cases
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
abstract class ControllersTestCase extends PHPUnit_Framework_TestCase
{
    protected $exceptionHandlers;
    
    protected function makeMiddlewareMock()
    {
        return $this->getMock(Middleware::class);
    }
    
    protected function makeExceptionHandlerMock()
    {
        return $this->getMock(ExceptionHandler::class);
    }
    
    protected function makeExceptionHandler($exceptionName, callable $callback = null)
    {
        $mock = $this->makeExceptionHandlerMock();
        $mock->method('getHandledExceptionName')
            ->will($this->returnValue($exceptionName));
        if ($callback) {
            $mock->method('handle')
                ->will($this->returnCallback($callback));
        }
        return $mock;
    }
    
    protected function addHandlerForException($exceptionName, callable $callback = null)
    {
        $mock = $this->makeExceptionHandler($exceptionName, $callback);
        $this->exceptionHandlers->add($mock);
        return $mock;
    }
    
}

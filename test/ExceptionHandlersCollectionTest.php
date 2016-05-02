<?php

use \Tys\Controllers\Contracts\ExceptionHandler;
use \Tys\Controllers\ExceptionHandlersCollection;

/**
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ExceptionHandlersCollectionTest extends ControllersTestCase
{   
    public function setUp()
    {
        $this->exceptionHandlers = new ExceptionHandlersCollection();
    }
    
    public function testAddMethod()
    {
        $mock = $this->makeExceptionHandler(Exception::class);
        $this->assertSame($this->exceptionHandlers, $this->exceptionHandlers->add($mock));
    }
    
    public function testGetHandlerForExceptionWhenEmpty()
    {
        $this->assertEmpty($this->exceptionHandlers->getHandlerForException(new Exception()));
    }
    
    public function testGetHandlerForExceptionFoundSame()
    {
        $mock = $this->addHandlerForException(InvalidArgumentException::class);
        $result = $this->exceptionHandlers->getHandlerForException(new InvalidArgumentException());
        $this->assertSame($mock, $result);
    }
    
    public function testHandlerForParentReturnedOnSubclass()
    {
        $mock = $this->addHandlerForException(Exception::class);
        $result = $this->exceptionHandlers->getHandlerForException(new InvalidArgumentException());
        $this->assertSame($mock, $result);
    }
    
    public function testFirstMatchingHandlerReturned()
    {
        $mock1 = $this->addHandlerForException(Exception::class);
        $this->addHandlerForException(Exception::class);
        $this->addHandlerForException(InvalidArgumentException::class);
        $result = $this->exceptionHandlers->getHandlerForException(new InvalidArgumentException());
        $this->assertSame($mock1, $result);
    }
}

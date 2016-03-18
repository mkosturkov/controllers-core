<?php

use \Tys\Controllers\Contracts\MiddlewareInterface;
use \Tys\Controllers\Controller;
use \Tys\Controllers\Exceptions\AlreadyRunningException;
use \Interop\Container\ContainerInterface;

/**
 * Tests for the Tys\Controllers\Controller class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ControllerTest extends ControllerTestCase
{

    private $dicStub;
    
    private $controller;

    private function makeRunnable()
    {
        return $this->getMock(MiddlewareInterface::class);
    }
    
    public function setUp()
    {
        $this->dicStub = $this->getMock(ContainerInterface::class);
        $this->controller = new Controller($this->dicStub);
    }
    
    public function testAppendCallback()
    {
        $this->checkRunAndRunOrder($this->controller, 'appendCallback', false);
    }
    
    public function testPrependCallback()
    {
        $this->checkRunAndRunOrder($this->controller, 'prependCallback', true);
    }
    
    public function testAppendFinalCallback()
    {
        $this->checkRunAndRunOrder($this->controller, 'appendFinalCallback', false);
    }
    
    public function testPrependFinalCallback()
    {
        $this->checkRunAndRunOrder($this->controller, 'prependFinalCallback', true);
    }
    
    public function testFinalCallbackAfterException()
    {
        $this->controller->appendCallback(function() {
            throw new Exception();
        });
        
        $ran = false;
        $this->controller->appendFinalCallback(function() use (&$ran) {
            $ran = true;
        });
        try {
            $this->controller->run();
        } catch (Exception $ex) {
            
        }
        $this->assertTrue($ran);
    }
    
    public function testFinalCallbackAfterHandledException()
    {
        $this->controller->appendCallback(function() {
            throw new Exception();
        });
        $this->controller->setExceptionHandlerCallback(Exception::class, function() {});
        $ran = false;
        $this->controller->appendFinalCallback(function() use (&$ran) {
            $ran = true;
        });
        $this->controller->run();
        $this->assertTrue($ran);
    }
    
    public function testLastValue()
    {
        $this->controller->appendCallback(function() {
            return 'test runned';
        });
        $this->controller->run();
        $this->assertEquals('test runned', $this->controller->getLastReturnValue());
    }
    
    public function testIsRunning()
    {
        $this->assertFalse($this->controller->isRunning());
        $running = false;
        $this->controller->appendCallback(function(Controller $controller) use (&$running) {
           $running = $controller->isRunning();
        });
        $this->controller->run();
        $this->assertTrue($running);
        $this->assertFalse($this->controller->isRunning());
    }
    
    public function testExceptionOnRunCallWhileRunning()
    {
        $this->controller->appendCallback(function(Controller $controller) {
            $controller->run();
        });
        $this->expectException(AlreadyRunningException::class);
        $this->controller->run();
    }
    
    public function testStoppedFlag()
    {
        $this->assertFalse($this->controller->isStopped());
        $this->controller->stop();
        $this->assertTrue($this->controller->isStopped());
        $this->controller->undoStop();
        $this->assertFalse($this->controller->isStopped());
    }
    
    public function testStopRun()
    {
        $ran = false;
        $notRan = true;
        $this->controller->appendCallback(function(Controller $controller) use (&$ran) {
            $ran = true;
            $controller->stop();
        });
        $this->controller->appendCallback(function() use (&$notRan) {
            $notRan = false;
        } );
        $this->controller->run();
        $this->assertTrue($ran);
        $this->assertTrue($notRan);
    }
    
    public function testStopOnException()
    {
        $this->controller->appendCallback(function() {
            throw new Exception();
        });
        $this->controller->setExceptionHandlerCallback(Exception::class, function() {});
        $this->controller->run();
        $this->assertTrue($this->controller->isStopped());
        
    }
    
    public function testContinueRunAfterException()
    {
        $this->controller->appendCallback(function() {
            throw new Exception();
        });
        $ran = false;
        $this->controller->appendCallback(function() use (&$ran) {
            $ran = true;
        });
        $this->controller->setExceptionHandlerCallback(Exception::class, function(Exception $ex, Controller $controller) {
            $controller->undoStop();
        });
        $this->controller->run();
        $this->assertTrue($ran);
    }
    
    public function testDICGetter()
    {
        $this->assertSame($this->dicStub, $this->controller->getDIC());
    }
    
    public function testUnhandledException()
    {
        $this->controller->appendCallback(function() {
            throw new Exception();
        });
        $this->expectException(Exception::class);
        $this->controller->run();
    }
    
    public function testExceptionHandlerSetterReturningSelf()
    {
        $this->assertSame($this->controller, $this->controller->setExceptionHandlerCallback(Exception::class, function() {
            
        }));
    }
    
    public function testExceptionHandlerBeingTriggered()
    {
        $thrown = new Exception();
        $this->controller->appendCallback(function() use ($thrown) {
            throw $thrown;
        });
        $cought = null;
        $this->controller->setExceptionHandlerCallback(Exception::class, function(Exception $e) use (&$cought) {
            $cought = $e;
        });
        $this->controller->run();
        $this->assertSame($thrown, $cought);
    }
    
    public function testExceptionHandlersForExceptionTypes()
    {
        $fe = function() {
            throw new Exception();
        };
        $fre = function() {
            throw new RuntimeException();
        };
        $fie = function() {
            throw new InvalidArgumentException();
        };
        $coughtBy = false;
        $this->controller->setExceptionHandlerCallback(InvalidArgumentException::class, function() use (&$coughtBy) {
            $coughtBy = 'invalid-argument-handler';
        });
        
        $this->controller->setExceptionHandlerCallback(Exception::class, function() use (&$coughtBy) {
            $coughtBy = 'exception-handler';
        });
        
        $this->controller->appendCallback($fe);
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
        
        $this->controller->undoStop();
        $this->controller->appendCallback($fre);
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
        
        $this->controller->undoStop();
        $this->controller->appendCallback($fie);
        $this->controller->run();
        $this->assertEquals('invalid-argument-handler', $coughtBy);
    }
    
    public function testExceptionHandlersPriority()
    {
        $coughtBy = false;
        $this->controller->setExceptionHandlerCallback(Exception::class, function() use (&$coughtBy) {
            $coughtBy = 'exception-handler';
        });
        $this->controller->setExceptionHandlerCallback(InvalidArgumentException::class, function() use (&$coughtBy) {
            $coughtBy = 'invalid-argument';
        });
        $this->controller->appendCallback(function() {
            throw new InvalidArgumentException();
        });
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
    }
    
    public function testQueueFlush()
    {
        $ran = false;
        $this->controller->appendCallback(function() use (&$ran) {
            $ran = true;
        });
        $this->assertSame($this->controller, $this->controller->flushQueue());
        $this->controller->run();
        $this->assertFalse($ran);
    }
    
}
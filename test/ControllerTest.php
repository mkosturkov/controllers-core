<?php

use \Tys\Controllers\Contracts\Middleware;
use \Tys\Controllers\Controller;
use \Tys\Controllers\Exceptions\AlreadyRunningException;

/**
 * Tests for the Tys\Controllers\Controller class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ControllerTest extends ControllersTestCase
{
    
    private $controller;
    
    public function setUp()
    {
        $this->controller = new Controller();
    }
    
    public function testAppendCallback()
    {
        $this->checkRunAndRunOrder($this->controller, 'appendCallback', false);
    }
    
    public function testPrependCallback()
    {
        $this->checkRunAndRunOrder($this->controller, 'prependCallback', true);
    }
    
    public function testAppendMiddleware()
    {
        $this->checkRunAndRunOrder(
            $this->controller,
            'appendMiddleware',
            false,
            Middleware::class,
            'run'
        );
    }
    
    public function testPrependMiddleware()
    {
        $this->checkRunAndRunOrder(
            $this->controller,
            'prependMiddleware',
            true,
            Middleware::class,
            'run'
        );
    }
    
    public function testAppendFinalCallback()
    {
        $this->controller->stop();
        $this->checkRunAndRunOrder($this->controller, 'appendFinalCallback', false);
    }
    
    public function testPrependFinalCallback()
    {
        $this->controller->stop();
        $this->checkRunAndRunOrder($this->controller, 'prependFinalCallback', true);
    }
    
    public function testAppendFinalMiddleware()
    {
        $this->controller->stop();
        $this->checkRunAndRunOrder($this->controller, 'appendFinalMiddleware', false, Middleware::class, 'run');
    }
    
    public function testPrependFinalMiddleware()
    {
        $this->controller->stop();
        $this->checkRunAndRunOrder($this->controller, 'prependFinalMiddleware', true, Middleware::class, 'run');
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
        $middleware = $this->makeMiddlewareMock();
        $middleware->expects($this->once())
            ->method('run')
            ->willReturn('test runned');
        $this->controller->appendMiddleware($middleware);
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
        $middleware = $this->makeMiddlewareMock();
        $middleware->expects($this->once())
            ->method('run')
            ->will($this->returnCallback(function ($controller) {
                $controller->stop();
            }));
        $this->controller->appendMiddleware($middleware);
        $middleware = $this->makeMiddlewareMock();
        $middleware->expects($this->never())
            ->method('run');
        $this->controller->appendMiddleware($middleware);
        $this->controller->run();
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
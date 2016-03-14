<?php

use \Tys\Controllers\Contracts\MiddlewareInterface;
use \Tys\Controllers\Controller;
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
    
    public function testAppendMiddleware()
    {
        $this->checkRunAndRunOrder(
            $this->controller,
            'appendMiddleware',
            false,
            MiddlewareInterface::class,
            'run'
        );
    }
    
    public function testPrependMiddleware()
    {
        $this->checkRunAndRunOrder(
            $this->controller,
            'prependMiddleware',
            true,
            MiddlewareInterface::class,
            'run'
        );
    }
    
    public function testLastValue()
    {
        $middleware = $this->makeRunnable();
        $middleware->expects($this->once())
            ->method('run')
            ->willReturn('test runned');
        $this->controller->appendMiddleware($middleware);
        $this->controller->run();
        $this->assertEquals('test runned', $this->controller->getLastReturnValue());
    }
    
    public function testStopRun()
    {
        $middleware = $this->makeRunnable();
        $middleware->expects($this->once())
            ->method('run')
            ->will($this->returnCallback(function ($controller) {
                $controller->stop();
            }));
        $this->controller->appendMiddleware($middleware);
        $middleware = $this->makeRunnable();
        $middleware->expects($this->never())
            ->method('run');
        $this->controller->appendMiddleware($middleware);
        $this->controller->run();
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
        
        $this->controller->appendCallback($fre);
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
        
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
    
}
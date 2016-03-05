<?php

use \Tys\Controllers\Controller;
use \Interop\Container\ContainerInterface;

/**
 * Tests for the Tys\Controllers\Controller class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ControllerTest extends PHPUnit_Framework_TestCase
{

    private $dicStub;
    
    private $controller;

    private function makeRunnable()
    {
        return $this->getMock(Tys\Controllers\Contracts\MiddlewareInterface::class);
    }
    
    public function setUp()
    {
        $this->dicStub = $this->getMock(ContainerInterface::class);
        $this->controller = new Controller($this->dicStub);
    }
    
    private function checkPrependAppendExecutionOrder($append)
    {
        $execOrder = [];
        if ($append) {
            $expectedOrder = ['first', 'second'];
            $method = 'appendMiddleware';
        } else {
            $expectedOrder = ['second', 'first'];
            $method = 'prependMiddleware';
        }
        
        $middleware = $this->makeRunnable();
        $middleware->expects($this->once())
            ->method('run')
            ->with($this->controller)
            ->will($this->returnCallback(function () use (&$execOrder) {
                $execOrder[] = 'first';
            }));
        $returnValue = $this->controller->$method($middleware);
        $this->assertSame($this->controller, $returnValue);
        
        $middleware = $this->makeRunnable();
        $middleware->expects($this->once())
            ->method('run')
            ->with($this->controller)
            ->will($this->returnCallback(function() use (&$execOrder) {
                $execOrder[] = 'second';
            }));
        $this->controller->$method($middleware);
        
        $this->controller->run();
        $this->assertEquals($expectedOrder, $execOrder);
    }
    
     public function testAppendMiddleware()
    {
        $this->checkPrependAppendExecutionOrder(true);
    }
    
    public function testPrependMiddleware()
    {
        $this->checkPrependAppendExecutionOrder(false);
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
}
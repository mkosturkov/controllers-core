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
    
}
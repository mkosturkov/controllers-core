<?php

use \Interop\Container\ContainerInterface;
use \Tys\Controllers\Controller;
use \Tys\Controllers\Middleware\ModelMiddleware\ModelMiddleware;
use \Tys\Controllers\Middleware\ModelMiddleware\ModelMiddlewareParams;

class TestModelMiddlwareObjectTestClass
{
    private $return;
    
    public function __construct($return = 'OK')
    {
        $this->return = $return;
    }
    
    public function testRun()
    {
        return $this->return;
    }
}

/**
 * A test for the ModelMiddleware class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ModelMiddlewareTest extends PHPUnit_Framework_TestCase
{
    private $dic;
    
    private $controller;
    
    public function setUp()
    {
        $this->dic = $this->getMock(ContainerInterface::class);
        $this->controller = $this->getMock(Controller::class, [], [$this->dic]);
    }
       
    public function testCallable()
    {
        $called = false;
        $return = mt_rand(0, 100);
        $params = [
            'type' => 'callable',
            'call' => function() use (&$called, &$return) {
                $called = true;
                return $return;
            }
        ];
        $sut = new ModelMiddleware(new ModelMiddlewareParams($params));
        $returned = $sut->run($this->controller);
        $this->assertEquals($return, $returned);
        $this->assertTrue($called);
    }
    
    public function testObject()
    {
        $params = ['type' => 'object', 'name' => TestModelMiddlwareObjectTestClass::class, 'call' => 'testRun'];
        $sut = new ModelMiddleware(new ModelMiddlewareParams($params));
        $returned = $sut->run($this->controller);
        $this->assertEquals('OK', $returned);
    }
    
    public function testService()
    {
        $params = ['type' => 'service', 'name' => 'test-class', 'call' => 'testRun'];
        $this->dic->expects($this->once())
            ->method('get')
            ->with('test-class')
            ->willReturn(new TestModelMiddlwareObjectTestClass('service'));
        $sut = new ModelMiddleware(new ModelMiddlewareParams($params));
        $this->assertEquals('service', $sut->run($this->controller));
    }
}

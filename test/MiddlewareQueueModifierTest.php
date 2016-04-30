<?php

use \Tys\Controllers\MiddlewareQueue;
use \Tys\Controllers\MiddlewareQueueModifier;

/**
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class MiddlewareQueueModifierTest extends ControllersTestCase
{
    private $queue;
    
    private $modifier;
    
    public function setUp()
    {
        $this->queue = $this->getMock(MiddlewareQueue::class);
        $this->modifier = new MiddlewareQueueModifier($this->queue);
    }
    
    public function testAppendMethod()
    {
        $this->callModifierMethod('append');
    }
    
    public function testPrependMethod()
    {
        $this->callModifierMethod('prepend');
    }
    
    private function callModifierMethod($method)
    {
        $middleware = $this->makeMiddlewareMock();
        $this->queue->expects($this->once())
            ->method($method)
            ->with($middleware);
        $this->assertSame($this->modifier, $this->modifier->$method($middleware));
    }
}

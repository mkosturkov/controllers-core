<?php

use \Tys\Controllers\LoopingMiddlewareQueue;

/**
 * Tests for the Tys\Controllers\LoopingMiddlewareQueue class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class LoopingMiddlewareQueueTest extends ControllersTestCase
{
    private $queue;
    
    public function setUp()
    {
        $this->queue = new LoopingMiddlewareQueue();
    }
    
    public function testLoopingNature()
    {
        $mock1 = $this->makeMiddlewareMock();
        $mock2 = $this->makeMiddlewareMock();
        $this->queue->append($mock1)
                    ->append($mock2);
        $this->assertSame($mock1, $this->queue->getNext());
        $this->assertSame($mock2, $this->queue->getNext());
        $this->assertTrue($this->queue->hasNext());
        $this->assertSame($mock1, $this->queue->getNext());
        $this->assertSame($mock2, $this->queue->getNext());
    }
}

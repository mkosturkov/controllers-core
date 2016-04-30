<?php

use \Tys\Controllers\MiddlewareQueue;

/**
 * Tests for the Tys\Controllers\MiddlewareQueue class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class MiddlewareQueueTest extends ControllersTestCase
{
    
    private $queue;
    
    public function setUp()
    {
        $this->queue = new MiddlewareQueue();
    }
    
    public function testEmptyQueueHasNoNextQueue()
    {
        $this->assertFalse($this->queue->hasNext());
    }
    
    public function testAppendMethod()
    {
        $returnValue = $this->queue->append($this->makeMiddlewareMock());
        $this->assertSame($this->queue, $returnValue);
        $this->assertTrue($this->queue->hasNext());
    }
    
    public function testFetchNextMethod()
    {
        $item = $this->makeMiddlewareMock();
        $this->queue->append($item);
        $this->assertSame($item, $this->queue->getNext());
    }
    
    public function testExceptionOnGetNextOnEmptyQueue()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->queue->getNext();
    }
    
    public function testGetNextIsRemovingItems()
    {
        $this->queue->append($this->makeMiddlewareMock())
              ->append($this->makeMiddlewareMock());
        $this->queue->getNext();
        $this->queue->getNext();
        $this->assertFalse($this->queue->hasNext());
    }
    
    public function testAppendItemsOrder()
    {
        $first = $this->makeMiddlewareMock();
        $second = $this->makeMiddlewareMock();
        $this->queue->append($first)
              ->append($second);
        $this->assertSame($first, $this->queue->getNext());
        $this->assertSame($second, $this->queue->getNext());
    }
    
    public function testPrependMethod()
    {
        $returnValue = $this->queue->prepend($this->makeMiddlewareMock());
        $this->assertTrue($this->queue->hasNext());
        $this->assertSame($this->queue, $returnValue);
    }
    
    public function testPrependItemsOrder()
    {
        $first = $this->makeMiddlewareMock();
        $second = $this->makeMiddlewareMock();
        $this->queue->prepend($first)
              ->prepend($second);
        $this->assertSame($second, $this->queue->getNext());
        $this->assertSame($first, $this->queue->getNext());
    }
    
    public function testFlushQueue()
    {
        $this->queue->prepend($this->makeMiddlewareMock());
        $this->queue->append($this->makeMiddlewareMock());
        $this->assertSame($this->queue, $this->queue->flush());
        $this->assertFalse($this->queue->hasNext());
    }

}

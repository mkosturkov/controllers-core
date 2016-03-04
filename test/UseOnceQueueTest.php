<?php

use \Tys\Controllers\UseOnceQueue;

/**
 * Tests for the Tys\Controllers\UseOnceQueue class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class UseOnceQueueTest extends PHPUnit_Framework_TestCase
{

    public function testEmptyHasNextQueue()
    {
        $queue = new UseOnceQueue();
        $this->assertFalse($queue->hasNext());
    }
    
    public function testAppendItem()
    {
        $queue = new UseOnceQueue();
        $returnValue = $queue->appendItem('first');
        $this->assertSame($queue, $returnValue);
        $this->assertTrue($queue->hasNext());
    }
    
    public function testFetchNextItem()
    {
        $queue = new UseOnceQueue();
        $queue->appendItem('item');
        $this->assertEquals('item', $queue->getNextItem());
    }
    
    public function testExceptionOnGetNextOnEmptyQueue()
    {
        $queue = new UseOnceQueue();
        $this->expectException(OutOfBoundsException::class);
        $queue->getNextItem();
    }
    
    public function testGetNextIsRemovingItems()
    {
        $queue = new UseOnceQueue();
        $queue->appendItem('first')
              ->appendItem('second');
        $queue->getNextItem();
        $queue->getNextItem();
        $this->assertFalse($queue->hasNext());
    }
    
    public function testAppendItemsOrder()
    {
        $queue = new UseOnceQueue();
        $queue->appendItem('first')
              ->appendItem('second');
        $this->assertEquals(['first', 'second'], [$queue->getNextItem(), $queue->getNextItem()]);
    }
    
    public function testPrependItem()
    {
        $queue = new UseOnceQueue();
        $returnValue = $queue->prependItem('first');
        $this->assertTrue($queue->hasNext());
        $this->assertSame($queue, $returnValue);
    }
    
    public function testPrependItemsOrder()
    {
        $queue = new UseOnceQueue();
        $queue->prependItem('second')
              ->prependItem('first');
        $this->assertEquals(['first', 'second'], [$queue->getNextItem(), $queue->getNextItem()]);
    }

}

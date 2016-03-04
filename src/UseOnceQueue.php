<?php

namespace Tys\Controllers;

/**
 * Holds an ordered collection of items.
 * Items can be prepended or appended to the collection.
 * Items can be fetched one by one 
 * from the beggining of the collection in sequential order only.
 * Once an item has been fetched it is removed from the collection.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class UseOnceQueue
{

    /**
     * Holds the items in the collection
     * @var array
     */
    private $items = [];
    
    /**
     * Tells wether there are items in the queue
     * @return bool true if there are items, false otherwise
     */
    public function hasNext()
    {
        return !empty ($this->items);
    }
    
    /**
     * Appends an item at the end of the queue
     * @param mixed $item The item
     * @return \Tys\Controllers\UseOnceQueue Returns itself
     */
    public function appendItem($item)
    {
        $this->items[] = $item;
        return $this;
    }
    
    /**
     * Prepends an item in the beggining of the queue
     * @param mixed $item The item
     * @return \Tys\Controllers\UseOnceQueue Returns itself
     */
    public function prependItem($item)
    {
        array_unshift($this->items, $item);
        return $this;
    }

    /**
     * Returns the next item in the queue
     * @return mixed
     * @throws \OutOfBoundsException When the queue is empty
     */
    public function getNextItem()
    {
        if ($this->hasNext()) {
            return array_shift($this->items);
        }
        throw new \OutOfBoundsException('The queue is empty!');
    }
    
}

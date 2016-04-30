<?php

namespace Tys\Controllers;

/**
 * Holds an ordered collection of middleware items.
 * Items can be prepended or appended to the collection.
 * Items can be fetched one by one 
 * from the beggining of the collection in sequential order only.
 * Once an item has been fetched it is appended to the end from the queue.
 *
 * @see UseOnceMiddlewareQueue
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class LoopingMiddlewareQueue extends UseOnceMiddlewareQueue
{
    public function getNext()
    {
        $middleware = parent::getNext();
        $this->append($middleware);
        return $middleware;
    }
}

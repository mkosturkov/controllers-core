<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\Middleware;

/**
 * Holds an ordered collection of middleware items.
 * Items can be prepended or appended to the collection.
 * Items can be fetched one by one 
 * from the beggining of the collection in sequential order only.
 * Once an item has been fetched it is removed from the collection.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class MiddlewareQueue
{

    /**
     * Holds the items in the collection
     * @var array
     */
    private $middlewareItems = [];
    
    /**
     * Tells wether there are items in the queue
     * @return bool true if there are items, false otherwise
     */
    public function hasNext()
    {
        return !empty ($this->middlewareItems);
    }
    
    /**
     * Appends a middleware item at the end of the queue
     * @param Middleware $middleware
     * @return this
     */
    public function append(Middleware $middleware)
    {
        $this->middlewareItems[] = $middleware;
        return $this;
    }
    
    /**
     * Prepends a middleware item in the beggining of the queue
     * @param Middleware $middleware
     * @return this
     */
    public function prepend(Middleware $middleware)
    {
        array_unshift($this->middlewareItems, $middleware);
        return $this;
    }

    /**
     * Returns the next middleware item in the queue
     * @return Middleware
     * @throws \OutOfBoundsException When the queue is empty
     */
    public function getNext()
    {
        if ($this->hasNext()) {
            return array_shift($this->middlewareItems);
        }
        throw new \OutOfBoundsException('The queue is empty!');
    }
    
    /**
     * Empties the queue
     * @return this
     */
    public function flush()
    {
        $this->middlewareItems = [];
        return $this;
    }
    
}

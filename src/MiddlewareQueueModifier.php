<?php

namespace Tys\Controllers;

use \Tys\Controllers\Contracts\Middleware;

/**
 * The class is used to wrap around MiddlewareQueue
 * and allow only appending and prepending to it.
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class MiddlewareQueueModifier
{
    private $queue;
    
    public function __construct(MiddlewareQueue $queue)
    {
        $this->queue = $queue;
    }
    
    /**
     * Appends a middleware item at the end of the queue
     * 
     * @param Middleware $middleware
     * @return this
     */
    public function append(Middleware $middleware)
    {
        $this->queue->append($middleware);
        return $this;
    }
    
    /**
     * Prepends a middleware item in the beggining of the queue
     * 
     * @param Middleware $middleware
     * @return this
     */
    public function prepend(Middleware $middleware)
    {
        $this->queue->prepend($middleware);
        return $this;
    }
}

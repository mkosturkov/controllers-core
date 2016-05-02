<?php

namespace Tys\Controllers\Contracts;

use \Tys\Controllers\Controller;

/**
 * Interface to implement for objects used as middleware
 * 
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
interface Middleware
{
    
    /**
     * This method will be called when the middleware is fired.
     * 
     * @param Controller $controller The controller instance running
     */
    public function run(Controller $controller);
}

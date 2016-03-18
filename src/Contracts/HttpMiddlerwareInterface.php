<?php

namespace Tys\Controllers\Contracts;

use \Tys\Controllers\HttpController;

/**
 * Middleware objects that are to be used
 * in the HttpController must implement this interface.
 * 
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
interface HttpMiddlerwareInterface
{

    /**
     * This method will be called when the middleware is fired.
     * 
     * @param HttpController $controller The controller instance running
     * @return mixed
     */
    public function httpRun(HttpController $controller);

}

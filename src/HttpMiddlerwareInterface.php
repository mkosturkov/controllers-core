<?php

namespace Tys\Controllers;

/**
 * Middleware objects that are to be used
 * in the HttpController must implement this interface.
 * 
 * @author Milko Kosturkov
 */
interface HttpMiddlerwareInterface
{

    /**
     * This method will be called when the middleware is fired.
     * 
     * @param \Tys\Controllers\HttpController $controller The controller instance running
     * @return mixed
     */
    public function run(HttpController $controller);

}

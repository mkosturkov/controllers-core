<?php

namespace Tys\Controllers\Contracts;

use \Tys\Controllers\HttpController;

/**
 * Middleware objects that are to be used
 * in the HttpController must implement this interface.
 * 
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
interface HttpMiddlerwareInterface extends MiddlewareInterface
{

    /**
     * {@inheritdoc}
     */
    public function run(HttpController $controller);

}

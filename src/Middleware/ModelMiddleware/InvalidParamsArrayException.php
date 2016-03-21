<?php

namespace Tys\Controllers\Middleware\ModelMiddleware;

/**
 * Exception thrown on suppliying invalid arguments
 * for the ModelMiddlewareParams class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class InvalidParamsArrayException extends \InvalidArgumentException
{
    const NO_TYPE = 100;
    const INVALID_TYPE = 200;
    const MISSING_CALL = 300;
    const MISSING_OBJECT_NAME = 400;
    const MISSING_METHOD_NAME = 500;
}

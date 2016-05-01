<?php

use \Tys\Controllers\Contracts\Middleware;

/**
 * Base for controllers test cases
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
abstract class ControllersTestCase extends PHPUnit_Framework_TestCase
{
    
    protected function makeMiddlewareMock()
    {
        return $this->getMock(Middleware::class);
    }
    
}

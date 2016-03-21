<?php

use \Tys\Controllers\Middleware\ModelMiddleware\ModelMiddlewareParams;
use \Tys\Controllers\Middleware\ModelMiddleware\InvalidParamsArrayException;

/**
 * Tests for the ModelMiddlewareParams class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ModelMiddlewareParamsTest extends PHPUnit_Framework_TestCase
{
    private function setExceptionWaiting($code)
    {
        $this->expectException(InvalidParamsArrayException::class);
        $this->expectExceptionCode($code);
    }
    
    public function testNoType()
    {
        $this->setExceptionWaiting(InvalidParamsArrayException::NO_TYPE);
        new ModelMiddlewareParams([]);
    }
    
    public function testInvalidType()
    {
        $this->setExceptionWaiting(InvalidParamsArrayException::INVALID_TYPE);
        new ModelMiddlewareParams(['type' => 'wrong type']);
    }
    
    public function testNoCall()
    {
        $this->setExceptionWaiting(InvalidParamsArrayException::MISSING_CALL);
        new ModelMiddlewareParams(['type' => 'callable']);
    }
    
    public function testOKCallableParams()
    {
        new ModelMiddlewareParams(['type' => 'callable', 'call' => 'a_function']);
    }
    
    public function testObjectMissingClass($type = 'object')
    {
        $this->setExceptionWaiting(InvalidParamsArrayException::MISSING_OBJECT_NAME);
        new ModelMiddlewareParams(['type' => $type, 'call' => 'method']);
    }
    
    public function testOKObjectParams($type = 'object')
    {
        new ModelMiddlewareParams(['type' => $type, 'name' => 'AClass', 'call' => 'method']);
    }
    
    public function testServiceMissingClass()
    {
        $this->testObjectMissingClass('service');
    }
    
    public function testOKServiceParams()
    {
        $this->testOKObjectParams('service');
    }
    
}

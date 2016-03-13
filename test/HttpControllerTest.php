<?php

use \Tys\Controllers\Exceptions\ResponseNotSetException;
use \Tys\Controllers\Contracts\HttpMiddlerwareInterface;
use \Tys\Controllers\HttpController;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

/**
 * Test for the \Tys\Controllers\HttpController class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class HttpControllerTest extends ControllerTestCase
{

    private $requestStub;
    
    private $controller;
    
    public function setUp()
    {
        $dicStub = $this->getMock(ContainerInterface::class);
        $this->requestStub = $this->getMock(RequestInterface::class);
        $this->controller = new HttpController($dicStub, $this->requestStub);
    }
    
    public function testRequestGetter()
    {
        $this->assertSame($this->requestStub, $this->controller->getRequest());
    }
    
    public function testResponseGetterSetter()
    {
        $response = $this->getMock(ResponseInterface::class);
        $returnValue = $this->controller->setResponse($response);
        $this->assertSame($this->controller, $returnValue);
        $this->assertSame($response, $this->controller->getResponse());
        
    }
    
    public function testResponseGetWhenNotSet()
    {
        $this->expectException(ResponseNotSetException::class);
        $this->controller->getResponse();
    }
    
    public function testAppendHttpMiddleware()
    {
        $this->checkRunAndRunOrder(
            $this->controller,
            'appendHttpMiddleware',
            false,
            HttpMiddlerwareInterface::class,
            'httpRun'
        );
    }
    
}

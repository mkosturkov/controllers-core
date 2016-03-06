<?php

/**
 * Base for controller test cases
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
abstract class ControllerTestCase extends PHPUnit_Framework_TestCase
{

    protected function checkRunAndRunOrder(
        $controller,
        $controllerMethodName,
        $middlewareName,
        $middlewareMethodName,
        $reversed
    )
    {
        $execOrder = [];
        $expectedOrder = ['first', 'second'];
        
        foreach ($expectedOrder as $item) {
            $middleware = $this->getMock($middlewareName);
            $middleware->expects($this->once())
                ->method($middlewareMethodName)
                ->with($controller)
                ->will($this->returnCallback(function () use (&$execOrder, $item) {
                    $execOrder[] = $item;
                }));
            $returnValue = $controller->$controllerMethodName($middleware);
            $this->assertSame($controller, $returnValue);
        }
        
        
        $controller->run();
        if ($reversed) {
            $this->assertEquals(array_reverse($expectedOrder), $execOrder);
        } else {
            $this->assertEquals($expectedOrder, $execOrder);
        }
    }
    
}

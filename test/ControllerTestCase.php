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
        $reversed,
        $middlewareName,
        $middlewareMethodName = null
    )
    {
        $execOrder = [];
        $expectedOrder = ['first', 'second'];
        
        foreach ($expectedOrder as $item) {
            $callback = function () use (&$execOrder, $item) {
                $execOrder[] = $item;
            };
            if ($middlewareMethodName) {
                $middleware = $this->getMock($middlewareName);
                $middleware->expects($this->once())
                    ->method($middlewareMethodName)
                    ->with($controller)
                    ->will($this->returnCallback($callback));
                $returnValue = $controller->$controllerMethodName($middleware);
            } else {
                $returnValue = $controller->$controllerMethodName($callback);
            }
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
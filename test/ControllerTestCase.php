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
        $reversed
    )
    {
        $execOrder = [];
        $expectedOrder = ['first', 'second'];
        
        foreach ($expectedOrder as $item) {
            $callback = function () use (&$execOrder, $item) {
                $execOrder[] = $item;
            };
            $returnValue = $controller->$controllerMethodName($callback);
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

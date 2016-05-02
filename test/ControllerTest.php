<?php

use \Tys\Controllers\MiddlewareQueueModifier;
use \Tys\Controllers\ExceptionHandlersCollection;
use \Tys\Controllers\Controller;
use \Tys\Controllers\Exceptions\AlreadyRunningException;

/**
 * Tests for the Tys\Controllers\Controller class
 *
 * @author Milko Kosturkov <mkosturkov@gmail.com>
 */
class ControllerTest extends ControllersTestCase
{   
    private $controller;
    
    private $mainQueue;
    
    private $finalQueue;
    
    public function setUp()
    {
        $this->controller = new Controller();
        $this->mainQueue = $this->controller->getMainQueueModifier();
        $this->finalQueue = $this->controller->getFinalQueueModifier();
        $this->exceptionHandlers = $this->controller->getExceptionHandlersCollection();
    }
    
    public function testQueueModifierGettersReturnTypes()
    {
        foreach (['getMainQueueModifier', 'getFinalQueueModifier'] as $methodName) {
            $this->assertInstanceOf(MiddlewareQueueModifier::class, $this->controller->$methodName());
        }
    }
    
    public function testGetExceptionHandlersCollection()
    {
        $this->assertInstanceOf(ExceptionHandlersCollection::class, $this->controller->getExceptionHandlersCollection());
    }
    
    public function testSetGetData()
    {
        foreach (['some value', 'other value'] as $value) {
            $this->assertSame($this->controller, $this->controller->setData($value));
            $this->assertEquals($value, $this->controller->getData());
        }
    }
    
    public function testStartQueueRun()
    {
        $result = $this->prepareForRunTest($this->mainQueue, false, [1, 2, 3]);
        $this->controller->run();
        $this->checkRunResult($result);
    }
    
    public function testReverseStartQueueRun()
    {
        $result = $this->prepareForRunTest($this->mainQueue, true, [1, 2, 3]);
        $this->controller->run();
        $this->checkRunResult($result);
    }
    
    public function testFinalQueueRun()
    {
        $result = $this->prepareForRunTest($this->finalQueue, false, [1, 2, 3]);
        $this->controller->run();
        $this->checkRunResult($result);
    }
    
    public function testReverseFinalQueueRun()
    {
        $result = $this->prepareForRunTest($this->finalQueue, true, [1, 2, 3]);
        $this->controller->run();
        $this->checkRunResult($result);
    }
    
    public function testStartQueueAndFinalQueueRun()
    {
        $queueResult = $this->prepareForRunTest($this->mainQueue, false, [1, 2, 3]);
        $finalQueueResult = $this->prepareForRunTest($this->finalQueue, false, [4, 5, 6]);
        $this->controller->run();
        $this->checkRunResult([
            'expected' => [$queueResult['expected'] + $finalQueueResult['expected']],
            'actual' => [$queueResult['actual'] + $finalQueueResult['actual']]
        ]);
    }
    
    public function testRethrowingUnhandledException()
    {
        $this->addExceptionInQueue();
        $this->expectException(\Exception::class);
        $this->controller->run();
    }
    
    public function testFinalCallbackAfterException()
    {
        $this->addExceptionInQueue();
        $ran = false;
        $this->appendCallback(function() use (&$ran) {
            $ran = true;
        }, $this->finalQueue);
        
        try {
            $this->controller->run();
        } catch (Exception $ex) {
            
        }
        $this->assertTrue($ran);
    }
    
    public function testFinalCallbackAfterHandledException()
    {
        $this->addHandlerForException(Exception::class, function() {});
        $this->addExceptionInQueue();
        $ran = false;
        $this->appendCallback(function() use (&$ran) {
            $ran = true;
        }, $this->finalQueue);
        $this->controller->run();
        $this->assertTrue($ran);
    }
    
    public function testIsRunning()
    {
        $this->assertFalse($this->controller->isRunning());
        $runningInQueue = false;
        $runningInFinalQueue = false;
        $this->appendCallback(function(Controller $controller) use (&$runningInQueue) {
            $runningInQueue = $controller->isRunning();
         });
         $this->appendCallback(function (Controller $controller) use (&$runningInFinalQueue) {
             $runningInFinalQueue = $controller->isRunning();
         }, $this->finalQueue);
        $this->controller->run();
        $this->assertTrue($runningInQueue);
        $this->assertTrue($runningInFinalQueue);
        $this->assertFalse($this->controller->isRunning());
    }
    
    public function testExceptionOnRunCallWhileRunning()
    {
        $this->appendCallback(function(Controller $controller) {
            $controller->run();
        });
        $this->expectException(AlreadyRunningException::class);
        $this->controller->run();
    }
    
    public function testStoppedFlag()
    {
        $this->assertFalse($this->controller->isStopped());
        $this->controller->stop();
        $this->assertTrue($this->controller->isStopped());
        $this->controller->undoStop();
        $this->assertFalse($this->controller->isStopped());
    }
    
    public function testStopRun()
    {
        $this->appendCallback(function ($controller) {
            $controller->stop();
        });
        $middleware = $this->makeMiddlewareMock();
        $middleware->expects($this->never())
            ->method('run');
        $this->mainQueue->append($middleware);
        $this->controller->run();
    }
    
    public function testStopOnException()
    {
        $this->addExceptionInQueue();
        $this->addHandlerForException(Exception::class, function() {});
        $this->controller->run();
        $this->assertTrue($this->controller->isStopped());
        
    }
    
    public function testContinueRunAfterException()
    {
        $this->addExceptionInQueue();
        $ran = false;
        $this->appendCallback(function() use (&$ran) {
            $ran = true;
        });
        $this->addHandlerForException(Exception::class, function(Controller $controller, Exception $ex) {
            $controller->undoStop();
        });
        $this->controller->run();
        $this->assertTrue($ran);
    }
    
    public function testUnhandledException()
    {
        $this->addExceptionInQueue();
        $this->expectException(Exception::class);
        $this->controller->run();
    }
    
    public function testExceptionHandlerBeingTriggered()
    {
        $thrown = new Exception();
        $this->appendCallback(function() use ($thrown) {
            throw $thrown;
        });
        $cought = null;
        $this->addHandlerForException(Exception::class, function(Controller $controller, Exception $e) use (&$cought) {
            $cought = $e;
        });
        $this->controller->run();
        $this->assertSame($thrown, $cought);
    }
    
    public function testExceptionHandlersForExceptionTypes()
    {
        $fe = function() {
            throw new Exception();
        };
        $fre = function() {
            throw new RuntimeException();
        };
        $fie = function() {
            throw new InvalidArgumentException();
        };
        $coughtBy = false;
        $this->addHandlerForException(InvalidArgumentException::class, function() use (&$coughtBy) {
            $coughtBy = 'invalid-argument-handler';
        });
        
        $this->addHandlerForException(Exception::class, function() use (&$coughtBy) {
            $coughtBy = 'exception-handler';
        });
        
        $this->appendCallback($fe);
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
        
        $this->controller->undoStop();
        $this->appendCallback($fre);
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
        
        $this->controller->undoStop();
        $this->appendCallback($fie);
        $this->controller->run();
        $this->assertEquals('invalid-argument-handler', $coughtBy);
    }
    
    public function testExceptionHandlersPriority()
    {
        $coughtBy = false;
        $this->addHandlerForException(Exception::class, function() use (&$coughtBy) {
            $coughtBy = 'exception-handler';
        });
        $this->addHandlerForException(InvalidArgumentException::class, function() use (&$coughtBy) {
            $coughtBy = 'invalid-argument';
        });
        $this->appendCallback(function() {
            throw new InvalidArgumentException();
        });
        $this->controller->run();
        $this->assertEquals('exception-handler', $coughtBy);
    }
    
    private function prepareForRunTest(MiddlewareQueueModifier $queueModifier, $reversed, array $fillItems)
    {
        $execOrder = [];
        $modifierMethod = $reversed ? 'prepend' : 'append';
        
        foreach ($fillItems as $item) {
            $callback = function () use (&$execOrder, $item) {
                $execOrder[] = $item;
            };
            $queueModifier->$modifierMethod($this->makeMiddlewareMockWithCallback($callback));
        }
        $expectedOrder = $reversed ? array_reverse($fillItems) : $fillItems;
        return ['expected' => $expectedOrder, 'actual' => &$execOrder];
    }
    
    private function checkRunResult(array $result)
    {
        return $this->assertEquals($result['expected'], $result['actual']);
    }
    
    private function makeMiddlewareMockWithCallback(callable $callback)
    {
        $mock = $this->makeMiddlewareMock();
        $mock->expects($this->once())
            ->method('run')
            ->with($this->controller)
            ->will($this->returnCallback($callback));
        return $mock;
    }
    
    private function addExceptionInQueue()
    {
        $this->controller->getMainQueueModifier()->append(
            $this->makeMiddlewareMockWithCallback(function() {
                throw new Exception();
            })
        );
    }
    
    private function appendCallback(callable $callback, MiddlewareQueueModifier $queue = null)
    {
        $queue = is_null($queue) ? $this->mainQueue : $queue;
        $queue->append($this->makeMiddlewareMockWithCallback($callback));
    }
    
}
<?php

use \Tys\Controllers\MiddlewareQueueModifier;
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
    
    private $queue;
    
    private $finalQueue;
    
    public function setUp()
    {
        $this->controller = new Controller();
        $this->queue = $this->controller->getQueueModifier();
        $this->finalQueue = $this->controller->getFinalQueueModifier();
    }
    
    public function testQueueModifierGettersReturnTypes()
    {
        foreach (['getQueueModifier', 'getFinalQueueModifier'] as $methodName) {
            $this->assertInstanceOf(MiddlewareQueueModifier::class, $this->controller->$methodName());
        }
    }
    
    public function testQueueRun()
    {
        $result = $this->prepareForRunTest($this->queue, false, [1, 2, 3]);
        $this->controller->run();
        $this->checkRunResult($result);
    }
    
    public function testReverseQueueRun()
    {
        $result = $this->prepareForRunTest($this->queue, true, [1, 2, 3]);
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
    
    public function testQueueAndFinalQueueRun()
    {
        $queueResult = $this->prepareForRunTest($this->queue, false, [1, 2, 3]);
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
        $this->addExceptionInQueue();
        $this->controller->setExceptionHandlerCallback(Exception::class, function() {});
        $ran = false;
        $this->appendCallback(function() use (&$ran) {
            $ran = true;
        }, $this->finalQueue);
        $this->controller->run();
        $this->assertTrue($ran);
    }
    
    public function testLastValue()
    {
        $middleware = $this->makeMiddlewareMock();
        $returnValue = 'test runned';
        $middleware->expects($this->once())
            ->method('run')
            ->willReturn($returnValue);
        $this->queue->append($middleware);
        $this->controller->run();
        $this->assertEquals($returnValue, $this->controller->getLastReturnValue());
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
        $this->queue->append($middleware);
        $this->controller->run();
    }
    
    public function testStopOnException()
    {
        $this->addExceptionInQueue();
        $this->controller->setExceptionHandlerCallback(Exception::class, function() {});
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
        $this->controller->setExceptionHandlerCallback(Exception::class, function(Exception $ex, Controller $controller) {
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
    
    public function testExceptionHandlerSetterReturningSelf()
    {
        $this->assertSame($this->controller, $this->controller->setExceptionHandlerCallback(Exception::class, function() {
            
        }));
    }
    
    public function testExceptionHandlerBeingTriggered()
    {
        $thrown = new Exception();
        $this->appendCallback(function() use ($thrown) {
            throw $thrown;
        });
        $cought = null;
        $this->controller->setExceptionHandlerCallback(Exception::class, function(Exception $e) use (&$cought) {
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
        $this->controller->setExceptionHandlerCallback(InvalidArgumentException::class, function() use (&$coughtBy) {
            $coughtBy = 'invalid-argument-handler';
        });
        
        $this->controller->setExceptionHandlerCallback(Exception::class, function() use (&$coughtBy) {
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
        $this->controller->setExceptionHandlerCallback(Exception::class, function() use (&$coughtBy) {
            $coughtBy = 'exception-handler';
        });
        $this->controller->setExceptionHandlerCallback(InvalidArgumentException::class, function() use (&$coughtBy) {
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
        $this->controller->getQueueModifier()->append(
            $this->makeMiddlewareMockWithCallback(function() {
                throw new Exception();
            })
        );
    }
    
    private function appendCallback(callable $callback, MiddlewareQueueModifier $queue = null)
    {
        $queue = is_null($queue) ? $this->queue : $queue;
        $queue->append($this->makeMiddlewareMockWithCallback($callback));
    }
    
}
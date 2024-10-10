<?php

namespace Axpecto\Aop\MethodInterception\Tests;

use Axpecto\Aop\MethodInterception\Method;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotationHandler;
use Axpecto\Aop\MethodInterception\MethodExecutionChain;
use Axpecto\Collection\Concrete\Klist;
use PHPUnit\Framework\TestCase;

class MethodExecutionChainTest extends TestCase
{
	public function testProceedWithoutAnnotationsCallsMethod()
	{
		// Create a mock for the Method class.
		$methodMock = $this->createMock(Method::class);

		// Expect the 'call' method to be called once and return 'result'.
		$methodMock->expects($this->once())
		           ->method('call')
		           ->willReturn('result');

		// Create an empty Klist for annotations.
		$annotations = new Klist();

		// Create the MethodExecutionChain with the mock method and empty annotations.
		$chain = new MethodExecutionChain($methodMock, $annotations);

		// Assert that proceeding returns 'result'.
		$this->assertEquals('result', $chain->proceed());
	}

	public function testProceedWithAnnotationCallsHandler()
	{
		// Create a mock for the Method class.
		$methodMock = $this->createMock(Method::class);

		// Create a mock for the MethodExecutionAnnotation class.
		$annotationMock = $this->createMock(MethodExecutionAnnotation::class);

		// Create a mock for the AnnotationHandler.
		$handlerMock = $this->createMock(MethodExecutionAnnotationHandler::class);

		// Expect the handler's intercept method to be called with the chain and annotation.
		$handlerMock->expects( $this->once() )
		            ->method('intercept')
		            ->with($this->isInstanceOf(MethodExecutionChain::class), $annotationMock)
		            ->willReturn('intercepted result');

		// Set the mock handler in the annotation.
		$annotationMock->method('getHandler')
		               ->willReturn($handlerMock);

		// Create a Klist with the mock annotation.
		$annotations = new Klist([$annotationMock]);

		// Create the MethodExecutionChain with the mock method and annotations.
		$chain = new MethodExecutionChain($methodMock, $annotations);

		// Assert that proceeding returns 'intercepted result'.
		$this->assertEquals('intercepted result', $chain->proceed());
	}

	public function testProceedWithNewMethod()
	{
		// Create a mock for the initial Method class.
		$initialMethodMock = $this->createMock(Method::class);

		// Create a mock for the new Method class.
		$newMethodMock = $this->createMock(Method::class);

		// Expect the 'call' method to be called once on the new method.
		$newMethodMock->expects($this->once())
		              ->method('call')
		              ->willReturn('new method result');

		// Create an empty Klist for annotations.
		$annotations = new Klist();

		// Create the MethodExecutionChain with the initial method and empty annotations.
		$chain = new MethodExecutionChain($initialMethodMock, $annotations);

		// Assert that proceeding with the new method returns 'new method result'.
		$this->assertEquals('new method result', $chain->proceed($newMethodMock));
	}
}

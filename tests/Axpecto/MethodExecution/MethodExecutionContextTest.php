<?php

namespace Axpecto\MethodExecution;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\MethodExecutionAnnotation;
use Axpecto\Collection\Klist;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class MethodExecutionContextTest extends TestCase {

	public function testProceedWithoutAnnotationsExecutesMethod() {
		// Mocking a simple method call with expected arguments
		$methodCall = function ( $arg1, $arg2 ) {
			return $arg1 + $arg2;
		};

		// Create a Klist of annotations (empty in this case)
		$queue = new Klist( [] );

		// Create the MethodExecutionContext with mock data
		$context = new MethodExecutionContext(
			className:  'TestClass',
			methodName: 'testMethod',
			methodCall: $methodCall( ... ),
			arguments:  [ 1, 2 ],
			queue:      $queue
		);

		// Assert that method executes and returns the expected result (1 + 2 = 3)
		$result = $context->proceed();
		$this->assertEquals( 3, $result );
	}

	/**
	 * @throws Exception
	 */
	public function testProceedWithAnnotationsInterception() {
		// Mock a method call that shouldn't be executed because of interception
		$methodCall = function () {
			return 'methodCalled';
		};

		// Create a mock MethodExecutionHandler
		$handlerMock = $this->createMock( MethodExecutionHandler::class );

		// Mock the handler to return a modified result instead of calling the method
		$handlerMock->expects( $this->once() )
		            ->method( 'intercept' )
		            ->willReturn( 'interceptedResult' );

		// Create a mock annotation with the handler
		$annotationMock = $this->createMock( MethodExecutionAnnotation::class );
		$annotationMock->expects( $this->once() )
			->method( 'getMethodExecutionHandler' )
			->willReturn( $handlerMock );

		// Create a Klist with one annotation
		$queue = new Klist( [ $annotationMock ] );

		// Create the MethodExecutionContext with mock data
		$context = new MethodExecutionContext(
			className:  'TestClass',
			methodName: 'testMethod',
			methodCall: $methodCall( ... ),
			arguments:  [],
			queue:      $queue
		);

		// Assert that the method call is intercepted and modified by the handler
		$result = $context->proceed();
		$this->assertEquals( 'interceptedResult', $result );
	}

	public function testAddArgument() {
		// Mocking a simple method call that returns the argument
		$methodCall = function ( $arg1 ) {
			return $arg1;
		};

		// Create a Klist of annotations (empty in this case)
		$queue = new Klist( [] );

		// Create the MethodExecutionContext
		$context = new MethodExecutionContext(
			className:  'TestClass',
			methodName: 'testMethod',
			methodCall: $methodCall( ... ),
			arguments:  [],
			queue:      $queue
		);

		// Add an argument to the method execution context
		$context->addArgument( 'arg1', 42 );

		// Assert that the method returns the added argument
		$result = $context->proceed();
		$this->assertEquals( 42, $result );
	}

	public function testProceedSkipsAnnotationWithoutHandler() {
		// Mock a method call
		$methodCall = function () {
			return 'methodCalled';
		};

		// Create an annotation without a handler
		$annotationMock = $this->createMock( MethodExecutionAnnotation::class );
		$annotationMock->method( 'getMethodExecutionHandler' )->willReturn( null );

		// Create a Klist with the mock annotation
		$queue = new Klist( [ $annotationMock ] );

		// Create the MethodExecutionContext with mock data
		$context = new MethodExecutionContext(
			className:  'TestClass',
			methodName: 'testMethod',
			methodCall: $methodCall( ... ),
			arguments:  [],
			queue:      $queue
		);

		// Assert that method is executed despite the annotation not having a handler
		$result = $context->proceed();
		$this->assertEquals( 'methodCalled', $result );
	}
}

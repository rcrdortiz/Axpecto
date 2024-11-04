<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationReader;
use Axpecto\MethodExecution\MethodExecutionContext;
use Axpecto\MethodExecution\MethodExecutionHandler;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;

class MethodExecutionProxyTest extends TestCase {
	private ReflectionUtils $reflectionUtilsMock;
	private AnnotationReader $annotationReaderMock;
	private MethodExecutionProxy $methodExecutionProxy;

	protected function setUp(): void {
		// Create mock objects for dependencies
		$this->reflectionUtilsMock  = $this->createMock( ReflectionUtils::class );
		$this->annotationReaderMock = $this->createMock( AnnotationReader::class );

		// Instantiate the MethodExecutionProxy with mocked dependencies
		$this->methodExecutionProxy = new MethodExecutionProxy( $this->reflectionUtilsMock, $this->annotationReaderMock );
	}

	public function testHandleExecutesMethodWithNoAnnotations(): void {
		$class     = 'TestClass';
		$method    = 'testMethod';
		$arguments = [ 'arg1' => 'value1' ];

		// Mock the method call closure
		$methodCall = function ( $arg1 ) {
			return $arg1;
		};

		// Mock empty annotations (no annotations are defined for the method)
		$this->annotationReaderMock
			->expects( $this->once() )
			->method( 'getMethodExecutionAnnotations' )
			->with( $class, $method )
			->willReturn( new \Axpecto\Collection\Klist() );

		// Mock reflection to map the method arguments correctly
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'mapValuesToArguments' )
			->with( $class, $method, $arguments )
			->willReturn( $arguments );

		// Execute the handle method
		$result = $this->methodExecutionProxy->handle( $class, $method, $methodCall, $arguments );

		// Assert that the method call was executed and returned the expected result
		$this->assertEquals( 'value1', $result );
	}

	public function testHandleExecutesWithAnnotations(): void {
		$class     = 'TestClass';
		$method    = 'testMethod';
		$arguments = [ 'arg1' => 'value1' ];

		// Mock the method call closure
		$methodCall = function ( $arg1 ) {
			return $arg1;
		};

		// Mock an annotation and its handler
		$annotationMock = $this->createMock( Annotation::class );
		$handlerMock    = $this->createMock( MethodExecutionHandler::class );
		$annotationMock
			->expects( $this->once() )
			->method( 'getMethodExecutionHandler' )
			->willReturn( $handlerMock );

		// Mock annotations (single annotation for the method)
		$annotations = new \Axpecto\Collection\Klist( [ $annotationMock ] );
		$this->annotationReaderMock
			->expects( $this->once() )
			->method( 'getMethodExecutionAnnotations' )
			->with( $class, $method )
			->willReturn( $annotations );

		// Mock reflection to map the method arguments correctly
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'mapValuesToArguments' )
			->with( $class, $method, $arguments )
			->willReturn( $arguments );

		// Mock the handler to intercept the method call
		$handlerMock
			->expects( $this->once() )
			->method( 'intercept' )
			->willReturn( 'interceptedValue' );

		// Execute the handle method
		$result = $this->methodExecutionProxy->handle( $class, $method, $methodCall, $arguments );

		// Assert that the handler intercepted the method execution and returned the expected result
		$this->assertEquals( 'interceptedValue', $result );
	}

	public function testHandleHandlesMultipleAnnotations(): void {
		$class     = 'TestClass';
		$method    = 'testMethod';
		$arguments = [ 'arg1' => 'value1' ];

		// Mock the method call closure
		$methodCall = function ( $arg1 ) {
			return $arg1;
		};

		// Mock two annotations and their handlers
		$annotationMock1 = $this->createMock( Annotation::class );
		$annotationMock2 = $this->createMock( Annotation::class );
		$handlerMock1    = $this->createMock( MethodExecutionHandler::class );
		$handlerMock2    = $this->createMock( MethodExecutionHandler::class );

		// Set up handlers for both annotations
		$annotationMock1
			->expects( $this->once() )
			->method( 'getMethodExecutionHandler' )
			->willReturn( $handlerMock1 );
		$annotationMock2
			->expects( $this->once() )
			->method( 'getMethodExecutionHandler' )
			->willReturn( $handlerMock2 );

		// Mock annotations (two annotations for the method)
		$annotations = new \Axpecto\Collection\Klist( [ $annotationMock1, $annotationMock2 ] );
		$this->annotationReaderMock
			->expects( $this->once() )
			->method( 'getMethodExecutionAnnotations' )
			->with( $class, $method )
			->willReturn( $annotations );

		// Mock reflection to map the method arguments correctly
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'mapValuesToArguments' )
			->with( $class, $method, $arguments )
			->willReturn( $arguments );

		// Mock the first handler to proceed to the next one
		$handlerMock1
			->expects( $this->once() )
			->method( 'intercept' )
			->willReturnCallback( function ( MethodExecutionContext $context ) {
				return $context->proceed();
			} );

		// Mock the second handler to return a final value
		$handlerMock2
			->expects( $this->once() )
			->method( 'intercept' )
			->willReturn( 'finalValue' );

		// Execute the handle method
		$result = $this->methodExecutionProxy->handle( $class, $method, $methodCall, $arguments );

		// Assert that the final handler returned the expected result
		$this->assertEquals( 'finalValue', $result );
	}
}

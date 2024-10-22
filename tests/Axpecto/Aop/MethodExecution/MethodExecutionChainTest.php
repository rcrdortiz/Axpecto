<?php

namespace Axpecto\Aop\MethodExecution;

use Axpecto\Aop\Annotation;
use Axpecto\Aop\MethodExecutionHandler;
use Axpecto\Collection\Concrete\Klist;
use Exception;
use PHPUnit\Framework\TestCase;

class MethodExecutionChainTest extends TestCase {

	public function testProceedWithoutAnnotationsCallsMethod() {
		$methodCall = function () {
			return 42;
		};
		$context    = new MethodExecutionContext( 'SomeClass', 'someMethod', $methodCall, [] );
		$queue      = new Klist(); // Empty queue (no annotations)
		$chain      = new MethodExecutionChain( $queue );

		$result = $chain->proceed( $context );
		$this->assertEquals( 42, $result, 'The method should return its original value.' );
	}

	public function testProceedWithSingleAnnotationInterceptsMethod() {
		$methodCall = function () {
			return 42;
		};
		$context    = new MethodExecutionContext( 'SomeClass', 'someMethod', $methodCall, [] );

		$mockAnnotation = $this->createMock( Annotation::class );
		$mockHandler    = $this->createMock( MethodExecutionHandler::class );

		// Set up the annotation to return the mock handler
		$mockAnnotation->method( 'getMethodExecutionHandler' )->willReturn( $mockHandler );

		// Intercept method execution and modify the context's return value
		$mockHandler->method( 'intercept' )->willReturnCallback( function ( $annotation, $context, $chain ) {
			$context->setReturnValue( 99 );

			return $context;
		} );

		$queue = new Klist( [ $mockAnnotation ] );
		$chain = new MethodExecutionChain( $queue );

		$result = $chain->proceed( $context );
		$this->assertEquals( 99, $result, 'The handler should override the return value.' );
	}

	public function testProceedWithMultipleAnnotations() {
		$methodCall = function () {
			return 42;
		};
		$context    = new MethodExecutionContext( 'SomeClass', 'someMethod', $methodCall, [] );

		$mockAnnotation1 = $this->createMock( Annotation::class );
		$mockHandler1    = $this->createMock( MethodExecutionHandler::class );

		$mockAnnotation2 = $this->createMock( Annotation::class );
		$mockHandler2    = $this->createMock( MethodExecutionHandler::class );

		// Set up handlers to intercept the chain
		$mockAnnotation1->method( 'getMethodExecutionHandler' )->willReturn( $mockHandler1 );
		$mockHandler1->method( 'intercept' )->willReturnCallback( function ( $annotation, $context, $chain ) {
			$context->setReturnValue( 99 );

			return $context;
		} );

		$mockAnnotation2->method( 'getMethodExecutionHandler' )->willReturn( $mockHandler2 );
		$mockHandler2->method( 'intercept' )->willReturnCallback( function ( $annotation, $context, $chain ) {
			$context->setReturnValue( $context->invokeMethod() + 1 );

			return $context;
		} );

		$queue = new Klist( [ $mockAnnotation1, $mockAnnotation2 ] );
		$chain = new MethodExecutionChain( $queue );

		$result = $chain->proceed( $context );
		$this->assertEquals( 100, $result, 'The method call result should be intercepted and modified by multiple handlers.' );
	}
}
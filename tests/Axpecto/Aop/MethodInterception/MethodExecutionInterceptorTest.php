<?php

namespace Axpecto\Aop\MethodInterception\Tests;

use Axpecto\Aop\MethodInterception\ExecutionChainFactory;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotationHandler;
use Axpecto\Aop\MethodInterception\MethodExecutionChain;
use Axpecto\Aop\MethodInterception\MethodExecutionInterceptor;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class MethodExecutionInterceptorTest extends TestCase {
	private ReflectionUtils $reflectionUtilsMock;
	private Container $containerMock;
	private MethodExecutionInterceptor $interceptor;

	protected function setUp(): void {
		$this->reflectionUtilsMock = $this->createMock( ReflectionUtils::class );
		$this->containerMock       = $this->createMock( Container::class );
		$this->chainFactoryMock    = $this->createMock( ExecutionChainFactory::class );

		$this->interceptor = new MethodExecutionInterceptor( $this->reflectionUtilsMock, $this->containerMock, $this->chainFactoryMock );
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function testInterceptWithAnnotations(): void {
		$class      = 'SomeClass';
		$method     = 'someMethod';
		$arguments  = [ 'arg1', 'arg2' ];
		$methodCall = function () {
			return 'originalMethodCall';
		};

		// Mock MethodExecutionAnnotation
		$methodAnnotation = new class( MethodExecutionAnnotationHandler::class ) extends MethodExecutionAnnotation {
		};

		// Mock ReflectionUtils behavior for getMethodAnnotations and getMethodArguments
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getMethodAnnotations' )
			->with( $class, $method, MethodExecutionAnnotation::class )
			->willReturn( listOf( $methodAnnotation ) );

		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getMethodArguments' )
			->with( $class, $method, $arguments )
			->willReturn( $arguments );

		$chainMock = $this->createMock( MethodExecutionChain::class );
		$chainMock->expects( $this->once() )
		          ->method( 'proceed' )
		          ->willReturn( 'modifiedOutput' );

		$this->chainFactoryMock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $chainMock );

		$handler = $this->createMock( MethodExecutionAnnotationHandler::class );
		$this->containerMock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $handler );

		// We can't mock Closure, so we skip testing that part in-depth
		$result = $this->interceptor->intercept( $class, $method, $methodCall, $arguments );

		// Assert that the final result is as expected
		$this->assertEquals( 'modifiedOutput', $result );
	}

	public function testInterceptWithoutAnnotations(): void {
		$class      = 'SomeClass';
		$method     = 'someMethod';
		$arguments  = [ 'arg1', 'arg2' ];
		$methodCall = function () {
			return 'originalMethodCall';
		};

		// Mock ReflectionUtils behavior for getMethodAnnotations and getMethodArguments
		$this->reflectionUtilsMock->expects( $this->once() )
		                          ->method( 'getMethodAnnotations' )
		                          ->with( $class, $method, MethodExecutionAnnotation::class )
		                          ->willReturn( emptyList() ); // No annotations

		$this->reflectionUtilsMock->expects( $this->once() )
		                          ->method( 'getMethodArguments' )
		                          ->with( $class, $method, $arguments )
		                          ->willReturn( $arguments );

		$chainMock = $this->createMock( MethodExecutionChain::class );
		$chainMock->expects( $this->once() )
		          ->method( 'proceed' )
		          ->willReturn( 'originalMethodCall' );

		$this->chainFactoryMock
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $chainMock );

		// Invoke the method without any annotations
		$result = $this->interceptor->intercept( $class, $method, $methodCall, $arguments );

		// Assert that the original method call result is returned
		$this->assertEquals( 'originalMethodCall', $result );
	}
}

<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the MethodExecutionBuildHandler class.
 */
class MethodExecutionBuildHandlerTest extends TestCase {
	private ReflectionUtils $reflectionUtilsMock;
	private BuildContext $buildContextMock;
	private Annotation $annotationMock;
	private MethodExecutionBuildHandler $methodExecutionBuildHandler;

	protected function setUp(): void {
		// Create mock objects for dependencies
		$this->reflectionUtilsMock = $this->createMock( ReflectionUtils::class );
		$this->buildContextMock    = $this->createMock( BuildContext::class );
		$this->annotationMock      = $this->createMock( Annotation::class );

		// Instantiate MethodExecutionBuildHandler with mocked dependencies
		$this->methodExecutionBuildHandler = new MethodExecutionBuildHandler( $this->reflectionUtilsMock );
	}

	public function testInterceptAddsMethodToContext(): void {
		$class          = 'TestClass';
		$method         = 'testMethod';
		$signature      = 'public function testMethod()';
		$implementation = "return \$this->proxy->handle('TestClass', 'testMethod', parent::testMethod(...), func_get_args());";

		// Set up the annotation to return the class and method
		$this->annotationMock->method( 'getAnnotatedClass' )->willReturn( $class );
		$this->annotationMock->method( 'getAnnotatedMethod' )->willReturn( $method );

		// Mock the reflection utility to return the method signature and implementation
		$this->reflectionUtilsMock->method( 'getMethodDefinitionString' )->with( $class, $method )->willReturn( $signature );
		$this->reflectionUtilsMock->method( 'getReturnType' )->with( $class, $method )->willReturn( 'string' ); // Non-void return type

		// Expect the method and property to be added to the context
		$this->buildContextMock->expects( $this->once() )->method( 'addMethod' )->with( $method, $signature, $implementation );
		$this->buildContextMock->expects( $this->once() )->method( 'addProperty' )->with( MethodExecutionProxy::class, $this->anything() );

		// Execute the intercept method
		$this->methodExecutionBuildHandler->intercept( $this->annotationMock, $this->buildContextMock );
	}

	public function testInterceptHandlesVoidMethods(): void {
		$class          = 'TestClass';
		$method         = 'voidMethod';
		$signature      = 'public function voidMethod()';
		$implementation = "\$this->proxy->handle('TestClass', 'voidMethod', parent::voidMethod(...), func_get_args());";

		// Set up the annotation to return the class and method
		$this->annotationMock->method( 'getAnnotatedClass' )->willReturn( $class );
		$this->annotationMock->method( 'getAnnotatedMethod' )->willReturn( $method );

		// Mock the reflection utility to return the method signature and void return type
		$this->reflectionUtilsMock->method( 'getMethodDefinitionString' )->with( $class, $method )->willReturn( $signature );
		$this->reflectionUtilsMock->method( 'getReturnType' )->with( $class, $method )->willReturn( 'void' ); // Void return type

		// Expect the method and property to be added to the context
		$this->buildContextMock->expects( $this->once() )->method( 'addMethod' )->with( $method, $signature, $implementation );
		$this->buildContextMock->expects( $this->once() )->method( 'addProperty' )->with( MethodExecutionProxy::class, $this->anything() );

		// Execute the intercept method
		$this->methodExecutionBuildHandler->intercept( $this->annotationMock, $this->buildContextMock );
	}
}

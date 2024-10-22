<?php

namespace Axpecto\Aop\BuildHandler;

use Axpecto\Aop\Annotation;
use Axpecto\Aop\Build\BuildChain;
use Axpecto\Aop\Build\BuildOutput;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class MethodExecutionBuildHandlerTest extends TestCase {
	private $reflectionUtilsMock;
	private $buildChainMock;
	private $buildOutputMock;
	private $annotationMock;
	private $methodExecutionBuildHandler;

	protected function setUp(): void {
		// Create mock objects for dependencies
		$this->reflectionUtilsMock = $this->createMock( ReflectionUtils::class );
		$this->buildChainMock      = $this->createMock( BuildChain::class );
		$this->buildOutputMock     = $this->createMock( BuildOutput::class );
		$this->annotationMock      = $this->createMock( Annotation::class );

		// Instantiate the MethodExecutionBuildHandler with mocked dependencies
		$this->methodExecutionBuildHandler = new MethodExecutionBuildHandler( $this->reflectionUtilsMock );
	}

	public function testInterceptAddsMethodToOutput(): void {
		$class          = 'TestClass';
		$method         = 'testMethod';
		$signature      = 'public function testMethod()';
		$implementation = 'return $this->proxy->handle( \'TestClass\', \'testMethod\', parent::testMethod(...), func_get_args() );';

		// Set up the annotation to return the class and method
		$this->annotationMock
			->method( 'getAnnotatedClass' )
			->willReturn( $class );
		$this->annotationMock
			->method( 'getAnnotatedMethod' )
			->willReturn( $method );

		// Mock the reflection utility to return the method signature and implementation
		$this->reflectionUtilsMock
			->method( 'getMethodDefinitionString' )
			->with( $class, $method )
			->willReturn( $signature );
		$this->reflectionUtilsMock
			->method( 'getReturnType' )
			->with( $class, $method )
			->willReturn( 'string' ); // Non-void return type

		// Expect the method and property to be added to the BuildOutput
		$this->buildOutputMock
			->expects( $this->once() )
			->method( 'addMethod' )
			->with( $method, $signature, $implementation );
		$this->buildOutputMock
			->expects( $this->once() )
			->method( 'addProperty' )
			->with( MethodExecutionProxy::class, $this->anything() );

		// Proceed with the build chain
		$this->buildChainMock
			->expects( $this->once() )
			->method( 'proceed' )
			->willReturn( $this->buildOutputMock );

		// Execute the intercept method
		$result = $this->methodExecutionBuildHandler->intercept( $this->buildChainMock, $this->annotationMock, $this->buildOutputMock );

		// Assert that the build output was modified correctly
		$this->assertSame( $this->buildOutputMock, $result );
	}

	public function testInterceptHandlesVoidMethods(): void {
		$class          = 'TestClass';
		$method         = 'voidMethod';
		$signature      = 'public function voidMethod()';
		$implementation = '$this->proxy->handle( \'TestClass\', \'voidMethod\', parent::voidMethod(...), func_get_args() );';

		// Set up the annotation to return the class and method
		$this->annotationMock
			->method( 'getAnnotatedClass' )
			->willReturn( $class );
		$this->annotationMock
			->method( 'getAnnotatedMethod' )
			->willReturn( $method );

		// Mock the reflection utility to return the method signature and void return type
		$this->reflectionUtilsMock
			->method( 'getMethodDefinitionString' )
			->with( $class, $method )
			->willReturn( $signature );
		$this->reflectionUtilsMock
			->method( 'getReturnType' )
			->with( $class, $method )
			->willReturn( 'void' ); // Void return type

		// Expect the method and property to be added to the BuildOutput
		$this->buildOutputMock
			->expects( $this->once() )
			->method( 'addMethod' )
			->with( $method, $signature, $implementation );
		$this->buildOutputMock
			->expects( $this->once() )
			->method( 'addProperty' )
			->with( MethodExecutionProxy::class, $this->anything() );

		// Proceed with the build chain
		$this->buildChainMock
			->expects( $this->once() )
			->method( 'proceed' )
			->willReturn( $this->buildOutputMock );

		// Execute the intercept method
		$result = $this->methodExecutionBuildHandler->intercept( $this->buildChainMock, $this->annotationMock, $this->buildOutputMock );

		// Assert that the build output was modified correctly
		$this->assertSame( $this->buildOutputMock, $result );
	}
}
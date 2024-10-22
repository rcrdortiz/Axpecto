<?php

namespace Axpecto\Aop\BuildHandler;

use Axpecto\Aop\AnnotationReader;
use Axpecto\Aop\MethodExecution\ExecutionChainFactory;
use Axpecto\Aop\MethodExecution\MethodExecutionChain;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;

class MethodExecutionProxyTest extends TestCase {

	private $reflectionUtilsMock;
	private $annotationReaderMock;
	private $executionChainFactoryMock;
	private $methodExecutionProxy;

	protected function setUp(): void {
		// Create mock objects for dependencies
		$this->reflectionUtilsMock       = $this->createMock( ReflectionUtils::class );
		$this->annotationReaderMock      = $this->createMock( AnnotationReader::class );
		$this->executionChainFactoryMock = $this->createMock( ExecutionChainFactory::class );

		// Instantiate the MethodExecutionProxy with mocked dependencies
		$this->methodExecutionProxy = new MethodExecutionProxy(
			$this->reflectionUtilsMock,
			$this->annotationReaderMock,
			$this->executionChainFactoryMock
		);
	}

	public function testHandleMethodExecutionWithoutAnnotations(): void {
		$class        = 'TestClass';
		$method       = 'testMethod';
		$arguments    = [ 'arg1' => 'value1' ];
		$methodResult = 'result';

		// Mock Closure for the method call
		$methodCall = function () use ( $methodResult ) {
			return $methodResult;
		};

		// Mocking getMethodExecutionAnnotations to return an empty list
		$this->annotationReaderMock
			->method( 'getMethodExecutionAnnotations' )
			->with( $class, $method )
			->willReturn( emptyList() );

		// Mocking mapValuesToArguments to return the same arguments
		$this->reflectionUtilsMock
			->method( 'mapValuesToArguments' )
			->with( $class, $method, $arguments )
			->willReturn( $arguments );

		// Mocking the execution chain to return the method result
		$executionChainMock = $this->createMock( MethodExecutionChain::class );
		$executionChainMock
			->method( 'proceed' )
			->willReturn( $methodResult );

		// Mocking the chain factory to return our mock execution chain
		$this->executionChainFactoryMock
			->method( 'get' )
			->willReturn( $executionChainMock );

		// Call the method and assert the returned result matches the expected output
		$result = $this->methodExecutionProxy->handle( $class, $method, $methodCall, $arguments );
		$this->assertEquals( $methodResult, $result );
	}

	public function testHandleMethodExecutionWithAnnotations(): void {
		$class        = 'TestClass';
		$method       = 'testMethod';
		$arguments    = [ 'arg1' => 'value1' ];
		$methodResult = 'modifiedResult';

		// Mock Closure for the method call
		$methodCall = function () {
			return 'originalResult';
		};

		// Mocking getMethodExecutionAnnotations to return a list of annotations
		$annotations = listOf( 'someAnnotation' );
		$this->annotationReaderMock
			->method( 'getMethodExecutionAnnotations' )
			->with( $class, $method )
			->willReturn( $annotations );

		// Mocking mapValuesToArguments to return the same arguments
		$this->reflectionUtilsMock
			->method( 'mapValuesToArguments' )
			->with( $class, $method, $arguments )
			->willReturn( $arguments );

		// Mocking the execution chain to return the modified method result
		$executionChainMock = $this->createMock( MethodExecutionChain::class );
		$executionChainMock
			->method( 'proceed' )
			->willReturn( $methodResult );

		// Mocking the chain factory to return our mock execution chain
		$this->executionChainFactoryMock
			->method( 'get' )
			->willReturn( $executionChainMock );

		// Call the method and assert the returned result matches the expected output
		$result = $this->methodExecutionProxy->handle( $class, $method, $methodCall, $arguments );
		$this->assertEquals( $methodResult, $result );
	}
}
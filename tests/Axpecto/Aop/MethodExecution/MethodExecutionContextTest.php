<?php

namespace Axpecto\Aop\MethodExecution;

use PHPUnit\Framework\TestCase;

class MethodExecutionContextTest extends TestCase {

	public function testInvokeMethodWithoutReturnValueOverride() {
		$className  = 'SomeClass';
		$methodName = 'someMethod';
		$arguments  = [ 1, 2 ];
		$methodCall = function ( $a, $b ) {
			return $a + $b;
		};

		$context = new MethodExecutionContext( $className, $methodName, $methodCall, $arguments );
		$result  = $context->invokeMethod();

		$this->assertEquals( 3, $result, 'Method should return the sum of the arguments' );
	}

	public function testInvokeMethodWithReturnValueOverride() {
		$className  = 'SomeClass';
		$methodName = 'someMethod';
		$arguments  = [ 1, 2 ];
		$methodCall = function ( $a, $b ) {
			return $a + $b;
		};

		$context = new MethodExecutionContext( $className, $methodName, $methodCall, $arguments );
		$context->setReturnValue( 42 );

		$result = $context->invokeMethod();
		$this->assertEquals( 42, $result, 'Method should return the overridden value instead of calling the method' );
	}

	public function testAddArgument() {
		$className  = 'SomeClass';
		$methodName = 'someMethod';
		$arguments  = [ 1 ];
		$methodCall = function ( $a, $b ) {
			return $a + $b;
		};

		$context = new MethodExecutionContext( $className, $methodName, $methodCall, $arguments );
		$context->addArgument( 'b', 3 );

		$result = $context->invokeMethod();
		$this->assertEquals( 4, $result, 'Method should return the correct sum after adding a new argument' );
	}

	public function testSetIsCallableFalse() {
		$className  = 'SomeClass';
		$methodName = 'someMethod';
		$arguments  = [ 1, 2 ];
		$methodCall = function ( $a, $b ) {
			return $a + $b;
		};

		$context = new MethodExecutionContext( $className, $methodName, $methodCall, $arguments );
		$context->setIsCallable( false );

		$result = $context->invokeMethod();
		$this->assertNull( $result, 'Method should not be invoked when setIsCallable is false' );
	}
}
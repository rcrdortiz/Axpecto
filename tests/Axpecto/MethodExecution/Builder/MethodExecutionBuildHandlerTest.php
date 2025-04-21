<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildOutput;
use Axpecto\Code\MethodCodeGenerator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Unit test for the MethodExecutionBuildHandler class.
 */
class MethodExecutionBuildHandlerTest extends TestCase {
	private MethodCodeGenerator $codeGen;
	private MethodExecutionBuildHandler $handler;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void {
		// mock the code generator
		$this->codeGen = $this->createMock( MethodCodeGenerator::class );
		$this->handler = new MethodExecutionBuildHandler( $this->codeGen );
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function testInterceptAddsProxyPropertyAndMethod(): void {
		// Dummy target class + method
		$className  = DummyClass::class;
		$methodName = 'sayHello';

		// 1) Prepare a fake annotation
		$annotation = $this->createMock( Annotation::class );
		$annotation
			->method( 'getAnnotatedClass' )
			->willReturn( $className );
		$annotation
			->method( 'getAnnotatedMethod' )
			->willReturn( $methodName );

		// 2) Stub the code generator to return a known signature
		$this->codeGen
			->expects( $this->once() )
			->method( 'implementMethodSignature' )
			->with( $className, $methodName )
			->willReturn( 'public function sayHello(string $who)' );

		// 3) Create a fresh BuildContext for our DummyClass
		$context = new BuildOutput( $className );

		// 4) Invoke the handler
		$this->handler->intercept( $annotation, $context );

		// 5a) Assert the proxy property was injected
		$this->assertTrue(
			$context->properties->offsetExists( MethodExecutionProxy::class ),
			'Expected a "proxy" property in BuildContext::properties'
		);

		// 5b) Assert the method was added
		$this->assertTrue(
			$context->methods->offsetExists( $methodName ),
			"Expected method \"$methodName\" in BuildContext::methods"
		);

		// 5c) Inspect the generated code for the correct handle(...) call
		$methodCode = $context->methods->toArray()[ $methodName ];
		$this->assertStringContainsString(
			"return \$this->proxy->handle('$className', '$methodName', parent::$methodName(...), func_get_args())",
			$methodCode
		);
	}
}

/**
 * Dummy class used in the test.
 */
class DummyClass {
	public function sayHello( string $who ): string {
		return "Hello, $who";
	}
}
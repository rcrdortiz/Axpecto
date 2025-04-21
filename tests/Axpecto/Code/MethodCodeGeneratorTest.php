<?php

namespace Axpecto\Code;

use Axpecto\Reflection\ReflectionUtils;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MethodCodeGeneratorTest extends TestCase {
	private MethodCodeGenerator $generator;
	private $reflectionUtils;

	protected function setUp(): void {
		// Mock ReflectionUtils so we can control which ReflectionMethod is returned
		$this->reflectionUtils = $this->createMock( ReflectionUtils::class );
		$this->generator       = new MethodCodeGenerator( $this->reflectionUtils );
	}

	public function testThrowsWhenNotAbstractOrIsPrivate(): void {
		// Prepare a ReflectionMethod for a concrete (non-abstract) public method
		$rMethod = new ReflectionMethod( TestClassConcrete::class, 'concreteMethod' );
		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getClassMethod' )
			->with( TestClassConcrete::class, 'concreteMethod' )
			->willReturn( $rMethod );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( "Can't implement non-abstract or private method " . TestClassConcrete::class . '::concreteMethod()' );

		$this->generator->implementMethodSignature( TestClassConcrete::class, 'concreteMethod' );
	}

	public function testGeneratesSignatureForPublicAbstractMethodWithTypes(): void {
		// Use the real ReflectionMethod on our abstract test class
		$rMethod = new ReflectionMethod( TestAbstract::class, 'foo' );
		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getClassMethod' )
			->with( TestAbstract::class, 'foo' )
			->willReturn( $rMethod );

		$sig = $this->generator->implementMethodSignature( TestAbstract::class, 'foo' );

		// Expected: public function foo(int &$x = 5, string ...$rest): string|int, for some reason the return type is reversed on core.
		$this->assertSame(
			'public function foo(int &$x = 5, string ...$rest): string|int',
			$sig
		);
	}

	public function testGeneratesSignatureForProtectedAbstractWithNullableReturnAndDefault(): void {
		$rMethod = new ReflectionMethod( TestAbstract::class, 'bar' );
		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getClassMethod' )
			->with( TestAbstract::class, 'bar' )
			->willReturn( $rMethod );

		$sig = $this->generator->implementMethodSignature( TestAbstract::class, 'bar' );

		// Expected: protected function bar(?string $name = 'default'): ?bool
		$this->assertSame(
			"protected function bar(?string \$name = 'default'): ?bool",
			$sig
		);
	}
}

/**
 * A concrete class with a non-abstract method for negative testing.
 */
class TestClassConcrete {
	public function concreteMethod(): void {
	}
}

/**
 * An abstract class containing the methods to be generated.
 */
abstract class TestAbstract {
	/**
	 * Abstract public method with:
	 *  - typed, by-reference, defaulted parameter
	 *  - variadic parameter
	 *  - union return type
	 */
	public abstract function foo(
		int &$x = 5,
		string ...$rest
	): int|string;

	/**
	 * Abstract protected method with:
	 *  - nullable return type
	 *  - defaulted nullable parameter
	 */
	protected abstract function bar(
		?string $name = 'default'
	): ?bool;
}

<?php
// tests/Axpecto/Reflection/ReflectionUtilsTest.php
namespace Axpecto\Reflection\Tests;

use Attribute;
use Axpecto\Collection\Klist;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * A tiny test attribute we can put on classes, methods and properties.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY )]
class TestAttribute {
}

interface DummyInterface {
}

#[TestAttribute]
class DummyClass {
	#[TestAttribute]
	public function annotatedMethod( int $x ): void {
	}

	public function nonAnnotatedMethod(): void {
	}

	public function methodWithParams( $a, $b ): void {
	}

	private function privateMethod(): void {
	}

	final public function finalMethod(): void {
	}

	public function __construct() {
	}
}

abstract class DummyAbstract {
	abstract public function doSomething( string $s, int $n = 0 ): void;

	public function concrete(): void {
	}
}

class DummyPropClass {
	#[TestAttribute]
	private int $foo = 10;
}

class DummyCtor {
	public function __construct( int $a, string $b = 'hi' ) {
	}
}

class ReflectionUtilsTest extends TestCase {
	private ReflectionUtils $utils;

	protected function setUp(): void {
		$this->utils = new ReflectionUtils();
	}

	public function testGetReflectionClassCaching(): void {
		$r1 = $this->utils->getReflectionClass( DummyClass::class );
		$r2 = $this->utils->getReflectionClass( DummyClass::class );
		$this->assertSame( $r1, $r2 );
	}

	public function testGetClassAttributes(): void {
		$attrs = $this->utils->getClassAttributes( DummyClass::class );
		$this->assertInstanceOf( Klist::class, $attrs );
		$list = $attrs->toArray();
		$this->assertCount( 1, $list );
		$this->assertInstanceOf( TestAttribute::class, $list[0] );
	}

	public function testGetMethodAttributes(): void {
		$attrs = $this->utils->getMethodAttributes( DummyClass::class, 'annotatedMethod' );
		$this->assertCount( 1, $attrs->toArray() );
		$this->assertInstanceOf( TestAttribute::class, $attrs->toArray()[0] );
	}

	public function testGetAnnotatedMethods(): void {
		$methods = $this->utils->getAnnotatedMethods( DummyClass::class, TestAttribute::class );
		$names   = array_map( fn( ReflectionMethod $m ) => $m->getName(), $methods->toArray() );
		$this->assertContains( 'annotatedMethod', $names );
		$this->assertNotContains( 'nonAnnotatedMethod', $names );
	}

	public function testGetMethodsFiltersConstructorPrivateAndFinal(): void {
		$methods = $this->utils->getMethods( DummyClass::class );
		$names   = array_map( fn( ReflectionMethod $m ) => $m->getName(), $methods->toArray() );

		$this->assertContains( 'annotatedMethod', $names );
		$this->assertContains( 'nonAnnotatedMethod', $names );
		$this->assertContains( 'methodWithParams', $names );

		$this->assertNotContains( 'privateMethod', $names );
		$this->assertNotContains( 'finalMethod', $names );
		$this->assertNotContains( '__construct', $names );
	}

	public function testGetAbstractMethods(): void {
		$methods = $this->utils->getAbstractMethods( DummyAbstract::class );
		$names   = array_map( fn( ReflectionMethod $m ) => $m->getName(), $methods->toArray() );
		$this->assertContains( 'doSomething', $names );
		$this->assertNotContains( 'concrete', $names );
	}

	public function testGetConstructorArguments(): void {
		$args = $this->utils->getConstructorArguments( DummyCtor::class );
		$this->assertInstanceOf( Klist::class, $args );
		$array = $args->toArray();
		$this->assertCount( 2, $array );

		/** @var Argument $first */
		$first = $array[0];
		$this->assertEquals( 'a', $first->name );
		$this->assertEquals( 'int', $first->type );
		$this->assertFalse( $first->nullable );
		$this->assertNull( $first->default );

		/** @var Argument $second */
		$second = $array[1];
		$this->assertEquals( 'b', $second->name );
		$this->assertEquals( 'string', $second->type );
		$this->assertTrue( $second->nullable || $second->default !== null );
		$this->assertEquals( 'hi', $second->default );
	}

	public function testGetAnnotatedProperties(): void {
		$props = $this->utils->getAnnotatedProperties( DummyPropClass::class, TestAttribute::class );
		$this->assertInstanceOf( Klist::class, $props );
		$arr = $props->toArray();
		$this->assertCount( 1, $arr );
		$this->assertEquals( 'foo', $arr[0]->name );
	}

	public function testSetPropertyValue(): void {
		$obj = new DummyPropClass();
		$this->utils->setPropertyValue( $obj, 'foo', 42 );

		$rp = new ReflectionProperty( DummyPropClass::class, 'foo' );
		$rp->setAccessible( true );
		$this->assertSame( 42, $rp->getValue( $obj ) );
	}

	public function testMapValuesToArguments(): void {
		$map = $this->utils->mapValuesToArguments( DummyClass::class, 'methodWithParams', [ 1 ] );
		$this->assertEquals( [ 'a' => 1, 'b' => null ], $map );
	}

	public function testIsInterface(): void {
		$this->assertTrue( $this->utils->isInterface( DummyInterface::class ) );
		$this->assertFalse( $this->utils->isInterface( DummyClass::class ) );
	}

	public function testGetClassMethod(): void {
		$rm = $this->utils->getClassMethod( DummyClass::class, 'nonAnnotatedMethod' );
		$this->assertInstanceOf( ReflectionMethod::class, $rm );
		$this->assertEquals( 'nonAnnotatedMethod', $rm->getName() );
	}
}

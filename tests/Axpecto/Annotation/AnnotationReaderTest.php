<?php

declare(strict_types=1);

namespace Axpecto\Annotation;

use Axpecto\Collection\Klist;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

class AnnotationReaderTest extends TestCase
{
	private ReflectionUtils $reflect;
	private Container       $container;
	private AnnotationReader $reader;

	protected function setUp(): void
	{
		$this->reflect   = $this->createMock(ReflectionUtils::class);
		$this->container = $this->createMock(Container::class);
		$this->reader    = new AnnotationReader($this->container, $this->reflect);
	}

	public function testGetClassAnnotationsFiltersByTypeAndInjects(): void
	{
		$class = stdClass::class;
		$good  = $this->createMock(DummyAnnotation::class);
		$bad   = $this->createMock(OtherAnnotation::class);

		// reflect returns both
		$this->reflect
			->method('getClassAttributes')
			->with($class)
			->willReturn(listOf($good, $bad));

		// container should inject only the good one
		$this->container
			->expects($this->once())
			->method('applyPropertyInjection')
			->with($good);

		// stub setAnnotatedClass
		$good
			->expects($this->once())
			->method('setAnnotatedClass')
			->with($class)
			->willReturn($good);

		$out = $this->reader->getClassAnnotations($class, DummyAnnotation::class);

		$this->assertInstanceOf(Klist::class, $out);
		$this->assertCount(1, $out);
		$this->assertSame($good, $out->firstOrNull());
	}

	public function testGetMethodAnnotationsAddsClassAndMethod(): void
	{
		$class   = stdClass::class;
		$method  = 'foo';
		$ann     = $this->createMock(DummyAnnotation::class);

		$this->reflect
			->method('getMethodAttributes')
			->with($class, $method)
			->willReturn(listOf($ann));

		$this->container
			->expects($this->once())
			->method('applyPropertyInjection')
			->with($ann);

		$ann
			->expects($this->once())
			->method('setAnnotatedClass')
			->with($class)
			->willReturnSelf();
		$ann
			->expects($this->once())
			->method('setAnnotatedMethod')
			->with($method)
			->willReturnSelf();

		$out = $this->reader->getMethodAnnotations($class, $method, DummyAnnotation::class);

		$this->assertCount(1, $out);
		$this->assertSame($ann, $out->firstOrNull());
	}

	public function testGetAllAnnotationsMergesClassAndMethods(): void
	{
		$class = stdClass::class;

		// class ann
		$c1 = $this->createMock(DummyAnnotation::class);
		$this->reflect
			->method('getClassAttributes')
			->with($class)
			->willReturn(listOf($c1));

		$c1
			->method('setAnnotatedClass')
			->willReturnSelf();
		$this->container
			->method('applyPropertyInjection')
			->willReturnCallback(fn($a) => $a);

		// method list
		$m1 = $this->createMock(DummyAnnotation::class);
		$method = new ReflectionMethod(TestSubject::class, 'bar');

		$this->reflect
			->method('getAnnotatedMethods')
			->with($class, DummyAnnotation::class)
			->willReturn(listOf($method));

		// when reading that method
		$this->reflect
			->method('getMethodAttributes')
			->with($class, 'bar')
			->willReturn(listOf($m1));

		$m1
			->method('setAnnotatedClass')
			->with($class)
			->willReturnSelf();
		$m1
			->method('setAnnotatedMethod')
			->with('bar')
			->willReturnSelf();

		$out = $this->reader->getAllAnnotations($class, DummyAnnotation::class);

		// should contain both c1 and m1
		$this->assertCount(2, $out);
		$this->assertEqualsCanonicalizing([$c1, $m1], $out->toArray());
	}
}


/**
 * Dummy attribute class for testing.
 */
#[\Attribute(\Attribute::TARGET_ALL)]
class DummyAnnotation extends Annotation
{
	public function __construct() {}
}

/** Another annotation, should be filtered out. */
#[\Attribute(\Attribute::TARGET_ALL)]
class OtherAnnotation extends Annotation
{
	public function __construct() {}
}

/** A dummy class with one method for testGetAllAnnotations. */
class TestSubject
{
	#[DummyAnnotation]
	public function bar(): void {}
}

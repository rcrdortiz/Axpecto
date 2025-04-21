<?php

declare(strict_types=1);

namespace Axpecto\Annotation;

use Axpecto\Collection\Klist;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionClass;
use ReflectionException;
use stdClass;
use Attribute;
use ReflectionParameter;
use ReflectionProperty;

class AnnotationReaderTest extends TestCase
{
	private ReflectionUtils   $reflect;
	private Container         $container;
	private AnnotationReader  $reader;

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

		$this->reflect
			->expects($this->once())
			->method('getClassAttributes')
			->with($class)
			->willReturn(listOf($good, $bad));

		// only $good should be injected
		$this->container
			->expects($this->once())
			->method('applyPropertyInjection')
			->with($good);

		$good
			->expects($this->once())
			->method('setAnnotatedClass')
			->with($class)
			->willReturnSelf();

		$out = $this->reader->getClassAnnotations($class, DummyAnnotation::class);

		$this->assertInstanceOf(Klist::class, $out);
		$this->assertCount(1, $out);
		$this->assertSame($good, $out->firstOrNull());
	}

	public function testGetMethodAnnotationsAddsClassAndMethod(): void
	{
		$class  = stdClass::class;
		$method = 'foo';
		$ann    = $this->createMock(DummyAnnotation::class);

		$this->reflect
			->expects($this->once())
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
		$c1    = $this->createMock(DummyAnnotation::class);
		$m1    = $this->createMock(DummyAnnotation::class);

		// class‐level
		$this->reflect
			->method('getClassAttributes')
			->with($class)
			->willReturn(listOf($c1));
		$c1->method('setAnnotatedClass')->willReturnSelf();
		$this->container->method('applyPropertyInjection')->willReturnCallback(fn($a) => $a);

		// method list
		$methodRef = new ReflectionMethod(TestSubject::class, 'bar');
		$this->reflect
			->method('getAnnotatedMethods')
			->with($class, DummyAnnotation::class)
			->willReturn(listOf($methodRef));

		// per‐method attributes
		$this->reflect
			->method('getMethodAttributes')
			->with($class, 'bar')
			->willReturn(listOf($m1));
		$m1->method('setAnnotatedClass')->willReturnSelf();
		$m1->method('setAnnotatedMethod')->willReturnSelf();

		$out = $this->reader->getAllAnnotations($class, DummyAnnotation::class);

		$this->assertCount(2, $out);
		$this->assertEqualsCanonicalizing([$c1, $m1], $out->toArray());
	}

	public function testGetParameterAnnotationsReturnsOnlyMatching(): void
	{
		// use a real ReflectionMethod on ParamSubject
		$this->reflect
			->expects($this->once())
			->method('getClassMethod')
			->with(ParamSubject::class, 'foo')
			->willReturn(new ReflectionMethod(ParamSubject::class, 'foo'));

		$paramAnn = $this->reader->getParameterAnnotations(
			ParamSubject::class,
			'foo',
			'x',
			ParamAnnotation::class
		);

		$this->assertCount(1, $paramAnn);
		$this->assertInstanceOf(ParamAnnotation::class, $paramAnn->firstOrNull());
	}

	public function testGetParameterAnnotationsEmptyWhenNoSuchParam(): void
	{
		$this->reflect
			->method('getClassMethod')
			->willReturn(new ReflectionMethod(ParamSubject::class, 'foo'));

		$empty = $this->reader->getParameterAnnotations(
			ParamSubject::class,
			'foo',
			'no_such',
			ParamAnnotation::class
		);

		$this->assertInstanceOf(Klist::class, $empty);
		$this->assertCount(0, $empty);
	}

	public function testGetPropertyAnnotationReturnsFirst(): void
	{
		// stub ReflectionClass so getProperty returns real reflection
		$reflectionClass = new ReflectionClass(PropSubject::class);
		$this->reflect
			->expects($this->once())
			->method('getReflectionClass')
			->with(PropSubject::class)
			->willReturn($reflectionClass);

		$ann = $this->reader->getPropertyAnnotation(
			PropSubject::class,
			'foo',
			PropAnnotation::class
		);

		$this->assertInstanceOf(PropAnnotation::class, $ann);
		$this->assertSame(PropSubject::class, $ann->getAnnotatedClass());
	}
}


//--------------------------------------------------------
// Dummy attributes and subjects for testing parameters & props
//--------------------------------------------------------

#[Attribute(Attribute::TARGET_PARAMETER)]
class ParamAnnotation extends Annotation
{
	public function __construct() {}
}

class ParamSubject
{
	public function foo(
		#[ParamAnnotation]
		$x,
		$y
	) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class PropAnnotation extends Annotation
{
	public function __construct() {}
}

class PropSubject
{
	#[PropAnnotation]
	public string $foo = '';
}


//--------------------------------------------------------
// Re‑use earlier DummyAnnotation, OtherAnnotation, TestSubject
//--------------------------------------------------------

#[Attribute(Attribute::TARGET_ALL)]
class DummyAnnotation extends Annotation { public function __construct() {} }

#[Attribute(Attribute::TARGET_ALL)]
class OtherAnnotation extends Annotation { public function __construct() {} }

class TestSubject
{
	#[DummyAnnotation]
	public function bar(): void {}
}

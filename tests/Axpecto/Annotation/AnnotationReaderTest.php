<?php

namespace Axpecto\Annotation;

use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;

class AnnotationReaderTest extends TestCase {
	private ReflectionUtils $reflectionUtils;
	private AnnotationReader $annotationReader;
	private Container $container;

	public static function methodDataProvider(): array {
		$a = new class extends Annotation {};
		$b = new class extends Annotation {};
		$c = new class extends Annotation {};
		return [
			'Empty list of annotations returns an empty list' => [
				'annotationClass' => Annotation::class,
				'list' => listOf(),
				'expected' => listOf(),
				'injectionCount' => 0,
			],
			'Annotations are filtered when they are not of the expected type' => [
				'annotationClass' => $c::class,
				'list' => listOf( $a, $a, $a, $b, $b, $c, $b ),
				'expected' => listOf( $c ),
				'injectionCount' => 1,
			],
			'Annotation are not filtered if they are of the expected type' => [
				'annotationClass' => $a::class,
				'list' => listOf( $a, $a, $a ),
				'expected' => listOf( $a, $a, $a ),
				'injectionCount' => 3,
			],
		];
	}

	protected function setUp(): void {
		// Mock the Container and ReflectionUtils dependencies
		$this->container       = $this->createMock( Container::class );
		$this->reflectionUtils = $this->createMock( ReflectionUtils::class );

		// Initialize AnnotationReader with mocked dependencies
		$this->annotationReader = new AnnotationReader( $this->container, $this->reflectionUtils );
	}

	/**
	 * @dataProvider methodDataProvider
	 */
	public function testGetMethodAnnotations( $annotationClass, $list, $expected, $injectionCount ): void {
		// Mock the getMethodAttributes call to return the mock attributes
		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getMethodAttributes' )
			->willReturn( $list );

		$this->container
			->expects( $this->exactly( $injectionCount ) )
			->method( 'applyPropertyInjection' );

		// Call the method
		$actual = $this->annotationReader->getMethodAnnotations( 'AnyClass', 'AnyMethod', $annotationClass );

		// Assert the filtered list of annotations is returned
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @dataProvider methodDataProvider
	 */
	public function testGetClassAnnotations( $annotationClass, $list, $expected, $injectionCount ): void {
		// Mock the getMethodAttributes call to return the mock attributes
		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getClassAttributes' )
			->willReturn( $list );

		$this->container
			->expects( $this->exactly( $injectionCount ) )
			->method( 'applyPropertyInjection' );

		// Call the method
		$actual = $this->annotationReader->getClassAnnotations( 'AnyClass', $annotationClass );

		// Assert the filtered list of annotations is returned
		$this->assertEquals( $expected, $actual );
	}
}

<?php

namespace Axpecto\ClassBuilder\Tests;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationService;
use Axpecto\Annotation\BuildAnnotation;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\ClassBuilder\BuildOutput;
use Axpecto\ClassBuilder\ClassBuilder;
use Axpecto\Collection\Klist;
use Axpecto\Collection\Kmap;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClassBuilderTest extends TestCase {
	private ReflectionUtils $reflectionUtilsMock;
	private AnnotationService $annotationServiceMock;
	private ClassBuilder $classBuilder;

	protected function setUp(): void {
		// Create mock objects for dependencies
		$this->reflectionUtilsMock  = $this->createMock( ReflectionUtils::class );
		$this->annotationServiceMock = $this->createMock( AnnotationService::class );

		// Instantiate the ClassBuilder with mocked dependencies
		$this->classBuilder = new ClassBuilder( $this->reflectionUtilsMock, $this->annotationServiceMock );
	}

	public function testBuildReturnsOriginalClassWhenNoAnnotations(): void {
		$class = 'TestClass';

		// Mock the reader to return no annotations
		$this->annotationServiceMock
			->expects( $this->once() )
			->method( 'getAllAnnotations' )
			->with( $class, BuildAnnotation::class )
			->willReturn( emptyList() );

		// Execute the build method
		$result = $this->classBuilder->build( $class );

		// Assert that the original class is returned (no proxy created)
		$this->assertEquals( $class, $result );
	}

	public function testBuildThrowsExceptionIfClassAlreadyBuilt(): void {
		$class = 'TestClass';

		// Set up the ClassBuilder with a previously built class
		$this->classBuilder = new ClassBuilder( $this->reflectionUtilsMock, $this->annotationServiceMock, [ $class => 'TestClassProxy' ] );

		$this->expectException( ClassAlreadyBuiltException::class );
		$this->expectExceptionMessage( $class );

		// Execute the build method, expecting an exception
		$this->classBuilder->build( $class );
	}

	public function testBuildGeneratesProxyClass(): void {
		$class                = self::class;
		$methodSignature      = 'public function testMethod()';
		$methodImplementation = 'return $this->proxy->handle(\'TestClass\', \'testMethod\', parent::testMethod(...), func_get_args());';
		$property             = '#[Inject] private MethodExecutionProxy $proxy;';

		// Mock the reader to return an annotation with a builder
		$annotationMock = $this->createMock( BuildAnnotation::class );
		$builderMock    = $this->createMock( BuildHandler::class );
		$annotationMock
			->method( 'getBuilder' )
			->willReturn( $builderMock );

		// Mock annotations and builders: first call returns annotations, second (for proxy class) returns empty
		$annotations = new Klist( [ $annotationMock ] );
		$this->annotationServiceMock
			->method( 'getAllAnnotations' )
			->willReturnCallback( function ( string $cls, string $annClass ) use ( $class, $annotations ) {
				if ( $cls === $class ) {
					return $annotations;
				}
				return emptyList();
			} );

		// Mock getMethodAnnotations for the method annotation loop
		$this->annotationServiceMock
			->method( 'getMethodAnnotations' )
			->willReturn( emptyList() );

		// Expect the builder to be called and add a method/property
		$builderMock
			->expects( $this->once() )
			->method( 'intercept' )
			->willReturnCallback( function ( Annotation $annotation, $context ) use ( $methodSignature, $methodImplementation, $property ) {
				$context->addMethod( 'testMethod', $methodSignature, $methodImplementation );
				$context->addProperty( 'proxy', $property );
			} );

		// Execute the build method
		$result = $this->classBuilder->build( $class );

		// Assert that a proxy class is generated and returned
		$this->assertEquals( 'Axpecto_ClassBuilder_Tests_ClassBuilderTest__x0', $result );
	}

	public function testBuildClassGeneratesCorrectClassName(): void {
		$class       = SampleClass::class;
		$buildOutput = new BuildOutput(
			$class,
			new Kmap( [ 'testMethod' => 'public function testMethod() {}' ] ),
			new Kmap( [ 'proxy' => '#[Inject] private MethodExecutionProxy $proxy;' ] )
		);

		// Mock reflection to indicate the class is not an interface
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'isInterface' )
			->with( $class )
			->willReturn( false );

		// Execute private method 'buildClass' using reflection
		$reflection = new ReflectionClass( $this->classBuilder );
		$method     = $reflection->getMethod( 'buildClass' );
		$method->setAccessible( true );

		$proxiedClassName = $method->invoke( $this->classBuilder, $class, $buildOutput, 0 );

		// Assert that the generated class name is correct
		$this->assertEquals( 'Axpecto_ClassBuilder_Tests_SampleClass__x0', $proxiedClassName );
	}
}

class SampleClass {
}
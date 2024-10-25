<?php

namespace Axpecto\ClassBuilder\Tests;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationReader;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\ClassBuilder\ClassBuilder;
use Axpecto\Collection\Concrete\Klist;
use Axpecto\Collection\Concrete\MutableKmap;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClassBuilderTest extends TestCase {
	private ReflectionUtils $reflectionUtilsMock;
	private AnnotationReader $annotationReaderMock;
	private ClassBuilder $classBuilder;

	protected function setUp(): void {
		// Create mock objects for dependencies
		$this->reflectionUtilsMock  = $this->createMock( ReflectionUtils::class );
		$this->annotationReaderMock = $this->createMock( AnnotationReader::class );

		// Instantiate the ClassBuilder with mocked dependencies
		$this->classBuilder = new ClassBuilder( $this->reflectionUtilsMock, $this->annotationReaderMock );
	}

	public function testBuildReturnsOriginalClassWhenNoAnnotations(): void {
		$class = 'TestClass';

		// Mock the reader to return no annotations
		$this->annotationReaderMock
			->expects( $this->once() )
			->method( 'getAllBuildAnnotations' )
			->with( $class )
			->willReturn( emptyList() );

		// Execute the build method
		$result = $this->classBuilder->build( $class );

		// Assert that the original class is returned (no proxy created)
		$this->assertEquals( $class, $result );
	}

	public function testBuildThrowsExceptionIfClassAlreadyBuilt(): void {
		$class = 'TestClass';

		// Set up the ClassBuilder with a previously built class
		$this->classBuilder = new ClassBuilder( $this->reflectionUtilsMock, $this->annotationReaderMock, [ $class => 'TestClassProxy' ] );

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
		$annotationMock = $this->createMock( Annotation::class );
		$builderMock    = $this->createMock( \Axpecto\ClassBuilder\BuildHandler::class );
		$annotationMock
			->method( 'getBuilder' )
			->willReturn( $builderMock );

		// Mock annotations and builders
		$annotations = new Klist( [ $annotationMock ] );
		$this->annotationReaderMock
			->expects( $this->once() )
			->method( 'getAllBuildAnnotations' )
			->with( $class )
			->willReturn( $annotations );

		// Mock the reflection utility to provide method signature and implementation
		$this->reflectionUtilsMock
			->method( 'getMethodDefinitionString' )
			->with( $class, 'testMethod' )
			->willReturn( $methodSignature );

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
		$this->assertEquals( 'Axpecto_ClassBuilder_Tests_ClassBuilderTest', $result );
	}

	public function testGenerateProxyClass(): void {
		$class       = SampleClass::class;
		$buildOutput = new BuildContext(
			new MutableKmap( [ 'testMethod' => 'public function testMethod() {}' ] ),
			new MutableKmap( [ 'proxy' => '#[Inject] private MethodExecutionProxy $proxy;' ] )
		);

		// Mock reflection to indicate the class is not an interface
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'isInterface' )
			->with( $class )
			->willReturn( false );

		// Execute private method 'generateProxyClass' using reflection
		$reflection = new ReflectionClass( $this->classBuilder );
		$method     = $reflection->getMethod( 'generateProxyClass' );
		$method->setAccessible( true );

		$proxiedClassName = $method->invoke( $this->classBuilder, $class, $buildOutput );

		// Assert that the generated class name is correct
		$this->assertEquals( 'Axpecto_ClassBuilder_Tests_SampleClass', $proxiedClassName );
	}
}

class SampleClass {
}
<?php

use Axpecto\Aop\AnnotationReader;
use Axpecto\Aop\Build\BuildChain;
use Axpecto\Aop\Build\BuildChainFactory;
use Axpecto\Aop\Build\BuildOutput;
use Axpecto\Build\ClassBuilder;
use Axpecto\Collection\Concrete\MutableKmap;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;

class ClassBuilderTest extends TestCase {

	private $reflectionUtils;
	private $buildChainFactory;
	private $annotationReader;

	protected function setUp(): void {
		$this->reflectionUtils   = $this->createMock( ReflectionUtils::class );
		$this->buildChainFactory = $this->createMock( BuildChainFactory::class );
		$this->annotationReader  = $this->createMock( AnnotationReader::class );
	}

	public function testBuildReturnsOriginalClassWhenNoOutput(): void {
		$class = 'SomeClass';

		// Create a mock BuildOutput that has no output.
		$buildOutput = $this->createMock( BuildOutput::class );
		$buildOutput->method( 'isEmpty' )->willReturn( true );

		// Configure the BuildChainFactory to return a build chain that produces empty output.
		$buildChain = $this->createMock( BuildChain::class );
		$buildChain->method( 'proceed' )->willReturn( $buildOutput );
		$this->buildChainFactory->method( 'get' )->willReturn( $buildChain );

		// Create an instance of ClassBuilder and call the build method.
		$classBuilder = new ClassBuilder( $this->reflectionUtils, $this->buildChainFactory, $this->annotationReader );
		$result       = $classBuilder->build( $class );

		// Assert that the original class is returned when no output is generated.
		$this->assertEquals( $class, $result );
	}

	public function testBuildThrowsExceptionForAlreadyBuiltClass(): void {
		$class = 'SomeClass';

		// Create an instance of ClassBuilder with a pre-built class in the cache.
		$classBuilder = new ClassBuilder( $this->reflectionUtils,
		                                  $this->buildChainFactory,
		                                  $this->annotationReader,
		                                  [ $class => 'ProxiedClass' ] );

		// Assert that ClassAlreadyBuiltException is thrown for an already built class.
		$this->expectException( ClassAlreadyBuiltException::class );

		$classBuilder->build( $class );
	}

	public function testBuildGeneratesProxyClassWhenOutputExists(): void {
		$anyClass = BuildChain::class;

		// Create a BuildOutput that has output.
		$buildOutput = new BuildOutput(
			methods:	new MutableKmap( [ 'method' => 'public function method(): void { echo "Hello, World!"; }' ] ),
			properties: new MutableKmap( [ 'property' => 'public $property;' ] )
		);

		// Configure the BuildChainFactory to return a build chain that produces non-empty output.
		$buildChain = $this->createMock( BuildChain::class );
		$buildChain->method( 'proceed' )->willReturn( $buildOutput );
		$this->buildChainFactory->method( 'get' )->willReturn( $buildChain );

		// Mock ReflectionUtils to simulate reflection behavior.
		$this->reflectionUtils->method( 'isInterface' )->willReturn( false );

		// Create an instance of ClassBuilder and call the build method.
		$classBuilder = new ClassBuilder( $this->reflectionUtils, $this->buildChainFactory, $this->annotationReader );
		$proxiedClass = $classBuilder->build( $anyClass );

		// Assert that a proxied class name is returned.
		$this->assertNotEquals( $anyClass, $proxiedClass );
		$this->assertStringContainsString( '_', $proxiedClass );
	}
}

<?php

namespace Axpecto\Aop\Tests;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildAnnotationHandler;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Aop\BuildInterception\BuildChainFactory;
use Axpecto\Aop\BuildInterception\BuildOutput;
use Axpecto\Aop\ClassBuilder;
use Axpecto\Aop\Exception\ClassAlreadyBuiltException;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class ClassBuilderTest extends TestCase {
	private ClassBuilder $classBuilder;
	private ReflectionUtils $reflectionUtils;
	private Container $container;
	private BuildOutput $buildOutput;
	private BuildChainFactory $buildChainFactory;
	private BuildChain $buildChain;

	protected function setUp(): void {
		$this->reflectionUtils   = $this->createMock( ReflectionUtils::class );
		$this->container         = $this->createMock( Container::class );
		$this->buildOutput       = $this->createMock( BuildOutput::class );
		$this->buildChainFactory = $this->createMock( BuildChainFactory::class );
		$this->buildChain        = $this->createMock( BuildChain::class );

		$this->classBuilder = new ClassBuilder( $this->reflectionUtils, $this->container, $this->buildChainFactory );
	}

	public function testBuildClassAlreadyBuiltException(): void {
		$this->expectException( ClassAlreadyBuiltException::class );

		$class = 'TestClass';
		// Pre-cache the class to simulate it being already built.
		$this->classBuilder = new ClassBuilder( $this->reflectionUtils,
		                                        $this->container,
		                                        $this->buildChainFactory,
		                                        [ $class => 'BuiltClass' ] );

		$this->classBuilder->build( $class );
	}

	public function testBuildClassWithoutAnnotations(): void {
		$class = 'TestClass';
		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->with( $class, BuildAnnotation::class )
			->willReturn( emptyList() );

		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getAnnotatedMethods' )
			->with( $class, BuildAnnotation::class )
			->willReturn( emptyList() );

		$this->buildChainFactory
			->expects( $this->once() )
			->method( 'get' )
			->willReturn( $this->buildChain );

		$this->buildChain
			->expects( $this->once() )
			->method( 'proceed' )
			->willReturn( $this->buildOutput );

		$this->buildOutput
			->expects( $this->once() )
			->method( 'hasOutput' )
			->willReturn( false );

		$result = $this->classBuilder->build( $class );

		$this->assertSame( $class, $result );
	}

	public function testBuildClassWithAnnotations(): void {
		$class             = 'TestClass';
		$classAnnotations  = new class( 'AnyClassBuilderClass' ) extends BuildAnnotation {
		};
		$methodAnnotations = new class( 'AnyMethodBuilderClass' ) extends BuildAnnotation {
		};

		$this->reflectionUtils
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->willReturn( listOf( $classAnnotations ) );

		$this->container
			->method( 'get' )
			->willReturn( $this->createMock( BuildAnnotationHandler::class ) );

		$this->reflectionUtils
			->method( 'getAnnotatedMethods' )
			->willReturn( listOf( $this->createMock( ReflectionMethod::class ) ) );
		$this->reflectionUtils
			->method( 'getMethodAnnotations' )
			->willReturn( listOf( $methodAnnotations ) );

		$this->buildChainFactory
			->expects( $this->exactly( 2 ) )
			->method( 'get' );

		$result = $this->classBuilder->build( $class );

		$this->assertStringContainsString( $class, $result );
	}

	public function testBindAnnotationHandlerWithBuilderClass(): void {
		$annotation = new class ( 'AnyBuilderClass' ) extends BuildAnnotation {
		};

		$builderInstance = $this->createMock( BuildAnnotationHandler::class );
		$this->container
			->expects( $this->once() )
			->method( 'get' )
			->with( 'AnyBuilderClass' )
			->willReturn( $builderInstance );

		$bindAnnotation = $this->invokePrivateMethod( $this->classBuilder, 'bindAnnotationHandler', [ $annotation ] );

		$this->assertSame( $annotation, $bindAnnotation );
	}

	public function testGenerateProxyClass(): void {
		$class           = 'TestClass';
		$className       = 'ProxyClass';
		$inheritanceType = 'extends';

		$method = $this->invokePrivateMethod(
			$this->classBuilder,
			'generateProxyClass',
			[ $class, $inheritanceType, $className, new BuildOutput() ]
		);

		$this->assertStringContainsString( "class ProxyClass extends $class", $method );
	}

	/**
	 * Invokes a private or protected method for testing.
	 *
	 * @param object $object     The object instance.
	 * @param string $methodName The private/protected method name.
	 * @param array  $parameters The method parameters.
	 *
	 * @return mixed The method result.
	 * @throws ReflectionException
	 */
	private function invokePrivateMethod( object $object, string $methodName, array $parameters = [] ): mixed {
		$reflection = new ReflectionClass( $object );
		$method     = $reflection->getMethod( $methodName );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}
}

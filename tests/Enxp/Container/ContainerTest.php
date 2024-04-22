<?php

namespace Enxp\Container;

use Enxp\Aop\InterceptedClassFactory;
use Enxp\Container\Annotation\Inject;
use Enxp\Container\Annotation\Singleton;
use Enxp\Container\Exception\ClassNotFoundException;
use Enxp\Container\Exception\CircularReferenceException;
use Enxp\Reflection\Dto\Argument;
use Enxp\Reflection\ReflectionUtils;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {

	public const ANY_NAME = 'any_name';
	public const ANY_VALUE = 'any_value';

	public function setUp(): void {
		parent::setUp();
		$this->reflectionUtilsMock         = $this->createMock( ReflectionUtils::class );
		$this->interceptedClassFactoryMock = $this->createMock( InterceptedClassFactory::class );
		$this->container                   = new Container( $this->reflectionUtilsMock, $this->interceptedClassFactoryMock );
	}

	public function testSetAndGetValue() {
		// Given
		$this->container->addValue( self::ANY_NAME, self::ANY_VALUE );

		// When
		$value = $this->container->get( self::ANY_NAME );

		// Then
		$this->assertEquals( self::ANY_VALUE, $value );
	}

	public function testInjectSingletonValue() {
		// Given
		$testClass           = InjectValueTestClass::class;
		$injectedClassMethod = listOf( new Argument( self::ANY_NAME, null ) );
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->willReturn( listOf( new Singleton() ) );
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getConstructorArguments' )
			->willReturn( $injectedClassMethod );
		$this->configureInterceptorForClass( $testClass );
		$this->container->addValue( self::ANY_NAME, self::ANY_VALUE );

		// When
		$firstInstance  = $this->container->get( $testClass );
		$secondInstance = $this->container->get( $testClass );

		// Then
		$this->assertTrue( $firstInstance instanceof $testClass );
		$this->assertTrue( $firstInstance === $secondInstance );
	}

	public function testInjectValue() {
		// Given
		$testClass           = InjectValueTestClass::class;
		$injectedClassMethod = listOf( new Argument( self::ANY_NAME, null ) );
		$this->container->addValue( self::ANY_NAME, self::ANY_VALUE );
		$this->reflectionUtilsMock
			->expects( $this->exactly( 2 ) )
			->method( 'getClassAnnotations' )
			->willReturn( emptyList() );
		$this->reflectionUtilsMock
			->expects( $this->exactly( 2 ) )
			->method( 'getConstructorArguments' )
			->willReturn( $injectedClassMethod );
		$this->configureInterceptorForClass( $testClass );

		// When
		$firstValue  = $this->container->get( $testClass );
		$secondValue = $this->container->get( $testClass );

		// Then
		$this->assertTrue( $firstValue instanceof $testClass );
		$this->assertTrue( $firstValue !== $secondValue );
	}

	public function testCircularReference() {
		// Given
		$injectedClassMethod = listOf( new Argument( 'ref', CircularReferenceTestClass::class ) );
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getConstructorArguments' )
			->willReturn( $injectedClassMethod );
		$this->configureInterceptorForClass( CircularReferenceTestClass::class );

		// Then
		$this->expectException( CircularReferenceException::class );

		// When
		$this->container->get( CircularReferenceTestClass::class );
	}

	public function testDependencyNotFound() {
		// Given
		$anyClass = CircularReferenceTestClass::class;
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getConstructorArguments' )
			->willReturn( listOf( new Argument( 'ref', 'NonExistent' ) ) );
		$this->configureInterceptorForClass( $anyClass, 'NonExistent' );

		// Then
		$this->expectException( ClassNotFoundException::class );

		// When
		$this->container->get( $anyClass );
	}

	public function testPropertyInjection() {
		// Given
		$class = PropertyInjectionTestClass::class;
		$injectedClass = new PropertyInjectionTestClass();
		$injectedClass->any_name = self::ANY_VALUE;
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getConstructorArguments' )
			->willReturn( emptyList() );
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getAnnotatedProperties' )
			->willReturn( listOf( new Argument( self::ANY_NAME, null ) ) );
		$this->reflectionUtilsMock
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->willReturn( emptyList() );
		$this->configureInterceptorForClass( $class );
		$this->container->addValue( self::ANY_NAME, self::ANY_VALUE );
		$this->reflectionUtilsMock
			->expects( $this->any() )
			->method( 'setPropertyValue' )
			->willReturn( $injectedClass );

		// When
		$object = $this->container->get( $class );

		// Then
		$this->assertEquals( self::ANY_VALUE, $object->any_name );
	}

	protected function configureInterceptorForClass( ...$class ) {
		$this->interceptedClassFactoryMock
			->expects( $this->any() )
			->method( 'getClassname' )
			->willReturn( ...$class );

	}
}

class CircularReferenceTestClass {
	public function __construct( CircularReferenceTestClass $ref ) {
	}
}

class PropertyInjectionTestClass {
	#[Inject]
	public string $any_name;
}

class InjectValueTestClass {
	public function __construct( $any_name ) {

	}
}
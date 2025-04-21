<?php

namespace Axpecto\ClassBuilder;

use Axpecto\Collection\Kmap;
use PHPUnit\Framework\TestCase;

class BuildOutputTest extends TestCase {
	private $methodsMock;
	private $propertiesMock;
	private $buildOutput;

	protected function setUp(): void {
		// Create mock for MutableKmap for methods and properties
		$this->methodsMock    = $this->createMock( Kmap::class );
		$this->propertiesMock = $this->createMock( Kmap::class );

		// Instantiate BuildOutput with the mocks
		$this->buildOutput = new BuildOutput( 'AnyClass', $this->methodsMock, $this->propertiesMock );
	}

	public function testAddMethod(): void {
		// Expect the `add` method to be called with the correct method name, signature, and implementation.
		$this->methodsMock
			->expects( $this->once() )
			->method( 'add' )
			->with(
				$this->equalTo( 'exampleMethod' ),
				$this->equalTo( "public function exampleMethod() {\n\t\treturn 'hello';\n\t}\n" )
			);

		// Add the method
		$this->buildOutput->addMethod( 'exampleMethod', 'public function exampleMethod()', "return 'hello';" );
	}

	public function testAddProperty(): void {
		// Expect the `add` method to be called with the correct property name and implementation.
		$this->propertiesMock
			->expects( $this->once() )
			->method( 'add' )
			->with(
				$this->equalTo( 'exampleProperty' ),
				$this->equalTo( 'private $exampleProperty = "value";' )
			);

		// Add the property
		$this->buildOutput->addProperty( 'exampleProperty', 'private $exampleProperty = "value";' );
	}

	public function testAdd(): void {
		// Create additional Kmap mocks for methods and properties to append
		$additionalMethods    = $this->createMock( Kmap::class );
		$additionalProperties = $this->createMock( Kmap::class );

		// Expect the merge method to be called for both methods and properties
		$this->methodsMock
			->expects( $this->once() )
			->method( 'merge' )
			->with( $additionalMethods );

		$this->propertiesMock
			->expects( $this->once() )
			->method( 'merge' )
			->with( $additionalProperties );

		// Call the `add` method with the additional methods and properties
		$this->buildOutput->add( $additionalMethods, $additionalProperties );
	}

	public function testIsEmptyWhenBothMethodsAndPropertiesAreEmpty(): void {
		// Expect `isEmpty` to return true for both methods and properties
		$this->methodsMock
			->expects( $this->once() )
			->method( 'isEmpty' )
			->willReturn( true );

		$this->propertiesMock
			->expects( $this->once() )
			->method( 'isEmpty' )
			->willReturn( true );

		// Call the `isEmpty` method and assert that it returns true
		$this->assertTrue( $this->buildOutput->isEmpty() );
	}

	public function testIsNotEmptyWhenMethodsOrPropertiesAreNotEmpty(): void {
		// Expect `isEmpty` to return false for methods and true for properties
		$this->methodsMock
			->expects( $this->once() )
			->method( 'isEmpty' )
			->willReturn( true );

		$this->propertiesMock
			->expects( $this->once() )
			->method( 'isEmpty' )
			->willReturn( false );

		// Call the `isEmpty` method and assert that it returns false
		$this->assertFalse( $this->buildOutput->isEmpty() );
	}
}
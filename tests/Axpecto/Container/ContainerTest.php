<?php


use PHPUnit\Framework\TestCase;
use Axpecto\Container\Container;
use Axpecto\Container\Exception\CircularReferenceException;
use Axpecto\Container\Exception\UnresolvedDependencyException;
use Axpecto\Container\Exception\AutowireDependencyException;

class ContainerTest extends TestCase {
	private Container $container;

	protected function setUp(): void {
		// Initialize a fresh container before each test
		$this->container = new Container();
	}

	/** @test */
	public function it_can_add_class_instance() {
		// Arrange: Create a mock class
		$mockInstance = new class {
			public string $name = 'TestClass';
		};

		// Act: Add the instance to the container
		$this->container->addClassInstance( 'TestClass', $mockInstance );

		// Assert: Ensure the instance is retrievable from the container
		$retrievedInstance = $this->container->get( 'TestClass' );
		$this->assertSame( $mockInstance, $retrievedInstance );
		$this->assertEquals( 'TestClass', $retrievedInstance->name );
	}

	/** @test */
	public function it_can_add_value() {
		// Act: Add a value to the container
		$this->container->addValue( 'config', 'my-config-value' );

		// Assert: Ensure the value is retrievable
		$this->assertEquals( 'my-config-value', $this->container->get( 'config' ) );
	}

	/** @test */
	public function it_can_bind_class_to_implementation() {
		// Arrange: Create a mock class and an alternative class
		$mockImplementation = new class {
			public function greet(): string {
				return 'Hello from Mock Implementation';
			}
		};

		// Act: Bind the class to the mock implementation
		$this->container->bind( 'MyClass', get_class( $mockImplementation ) );
		$this->container->addClassInstance( get_class( $mockImplementation ), $mockImplementation );

		// Assert: Ensure the correct class is instantiated and retrieved
		$instance = $this->container->get( 'MyClass' );
		$this->assertSame( $mockImplementation, $instance );
		$this->assertEquals( 'Hello from Mock Implementation', $instance->greet() );
	}

	/** @test */
	public function it_can_autowire_class_dependencies() {
		// Arrange: Create a class with dependencies
		$classWithDependencies = new class {
			public function __construct( public string $dependency = 'any' ) {
			}
		};

		// Act: Add the dependency to the container
		$this->container->addValue( 'dependency', 'DependencyValue' );

		// Assert: Ensure dependencies are autowired correctly
		$instance = $this->container->get( $classWithDependencies::class );
		$this->assertEquals( 'DependencyValue', $instance->dependency );
	}

	/** @test */
	public function it_throws_exception_for_circular_references() {
		$this->expectException( CircularReferenceException::class );
		$this->container->get( ClassA::class );
	}

	/** @test */
	public function it_throws_exception_for_unresolved_dependencies() {
		// Act & Assert: Ensure unresolved dependencies throw exception
		$this->expectException( AutowireDependencyException::class );
		$this->container->get( 'NonExistentClass' );
	}

	/** @test */
	public function it_throws_autowire_exception_for_invalid_class() {
		// Act & Assert: Ensure invalid classes throw AutowireDependencyException
		$this->expectException( AutowireDependencyException::class );
		$this->container->get( 'InvalidClass' );
	}
}

class ClassA {
	public function __construct( public ?ClassB $classB = null ) {
	}
}

class ClassB{
	public function __construct( public ?ClassA $classA = null ) {
	}
}
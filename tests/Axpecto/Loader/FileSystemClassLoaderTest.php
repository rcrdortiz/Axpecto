<?php

use PHPUnit\Framework\TestCase;
use Axpecto\Loader\FileSystemClassLoader;
use TestNamespace\TestClass;

class FileSystemClassLoaderTest extends TestCase {
	/**
	 * @var FileSystemClassLoader
	 */
	private FileSystemClassLoader $classLoader;

	/**
	 * Sets up the test environment with a new FileSystemClassLoader instance.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->classLoader = new FileSystemClassLoader();
	}

	/**
	 * Tests that a path can be successfully registered.
	 */
	public function testRegisterPath(): void {
		$this->classLoader->registerPath( 'TestNamespace', '/path/to/classes' );
		$this->assertArrayHasKey( 'TestNamespace', $this->classLoader->getRegisteredPaths() );
		$this->assertEquals( '/path/to/classes', $this->classLoader->getRegisteredPaths()['TestNamespace'] );
	}

	/**
	 * Tests that a class file can be loaded from a file and returns the correct class name.
	 */
	public function testLoadClassInFile(): void {
		$filePath = __DIR__ . '/Fixtures/TestClass.php';

		$loadedClass = $this->classLoader->loadClassInFile( $filePath );

		$this->assertEquals( TestClass::class, $loadedClass );
		$this->assertTrue( class_exists( TestClass::class ) );
	}

	/**
	 * Tests that an exception is thrown when the file does not exist or is unreadable.
	 */
	public function testLoadClassInFileThrowsExceptionForInvalidFile(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->classLoader->loadClassInFile( '/invalid/path/to/class.php' );
	}

	/**
	 * Tests that the class is loaded correctly using loadClass.
	 */
	public function testLoadClass(): void {
		$this->classLoader->registerPath( 'TestNamespace', __DIR__ . '/Fixtures' );

		$this->assertTrue( $this->classLoader->loadClass( TestClass::class ) );
		$this->assertTrue( class_exists( TestClass::class ) );
	}

	/**
	 * Tests that loadClass returns false if the class cannot be found.
	 */
	public function testLoadClassReturnsFalseForNonExistentClass(): void {
		$this->classLoader->registerPath( 'TestNamespace', __DIR__ . '/InvalidPath' );
		$this->assertFalse( $this->classLoader->loadClass( 'TestNamespace\\NonExistentClass' ) );
	}

	/**
	 * Tests that the file path variants for class loading are generated correctly.
	 */
	public function testGetClassFileVariants(): void {
		$reflection = new ReflectionClass( FileSystemClassLoader::class );
		$method     = $reflection->getMethod( 'getClassFileVariants' );
		$method->setAccessible( true );

		$filePath = '/path/to/class';
		$class    = 'TestClass';

		$expectedVariants = [
			'/path/to/class/TestClass.php',
			'/path/to/class/class-TestClass.php',
			'/path/to/class/interface-TestClass.php',
			'/path/to/class/trait-TestClass.php',
			'/path/to/class/TestClass.php',
			'/path/to/class/class-TestClass.php',
			'/path/to/class/interface-TestClass.php',
			'/path/to/class/trait-TestClass.php',
		];

		$actualVariants = $method->invoke( $this->classLoader, $filePath, $class );

		$this->assertEquals( $expectedVariants, $actualVariants );
	}
}

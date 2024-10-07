<?php

namespace Axpecto\Loader;

use Exception;
use InvalidArgumentException;

/**
 * Class FileSystemClassLoader
 *
 * A class loader that supports autoloading from registered paths based on namespace and class name.
 */
class FileSystemClassLoader {
	/**
	 * FileSystemClassLoader constructor.
	 *
	 * @param array<string, string> $registeredPaths Registered namespace paths.
	 * @param array<string, string> $loadedClasses   Loaded classes mapped by file path.
	 */
	public function __construct(
		private array $registeredPaths = [],
		private array $loadedClasses = [],
	) {
		// Register the class loader with the SPL autoloader stack.
		spl_autoload_register( [ $this, 'loadClass' ] );
	}

	/**
	 * Registers a namespace with a corresponding file path.
	 *
	 * @param string $namespace The namespace to register.
	 * @param string $path      The base directory for the namespace.
	 *
	 * @return static
	 */
	public function registerPath( string $namespace, string $path ): static {
		$this->registeredPaths[ $namespace ] = rtrim( $path, '/' );

		return $this;
	}

	/**
	 * Returns the list of registered namespace paths.
	 *
	 * @return array<string>
	 */
	public function getRegisteredPaths(): array {
		return $this->registeredPaths;
	}

	/**
	 * Loads a class from a specific file and returns the class name.
	 *
	 * @param string $filename The file path to load the class from.
	 *
	 * @return string|null The loaded class name or null if not found.
	 *
	 * @throws InvalidArgumentException If the file doesn't exist or is unreadable.
	 * @throws Exception On other errors while loading the file.
	 */
	public function loadClassInFile( string $filename ): ?string {
		$filename = strtolower( $filename );

		if ( isset( $this->loadedClasses[ $filename ] ) ) {
			return $this->loadedClasses[ $filename ];
		}

		if ( ! file_exists( $filename ) || ! is_readable( $filename ) ) {
			throw new InvalidArgumentException( "Could not load file: $filename" );
		}

		$classesBefore = get_declared_classes();
		ob_start();
		require_once $filename;
		ob_end_clean();
		$classesAfter = get_declared_classes();

		$newClass = array_diff( $classesAfter, $classesBefore );
		$newClass = array_shift( $newClass );

		if ( $newClass ) {
			$this->loadedClasses[ $filename ] = $newClass;

			return $newClass;
		}

		return null;
	}

	/**
	 * Attempts to load a class based on its namespace and class name.
	 *
	 * @param string $className The fully qualified class name.
	 *
	 * @return bool True if the class was successfully loaded, false otherwise.
	 */
	public function loadClass( string $className ): bool {
		$parts = explode( '\\', $className );
		if ( count( $parts ) < 2 ) {
			return false;
		}

		$class     = array_pop( $parts );
		$namespace = array_shift( $parts );
		$basePath  = $this->registeredPaths[ $namespace ] ?? null;

		if ( ! $basePath ) {
			return false;
		}

		$filePath      = $basePath . '/' . implode( '/', $parts );
		$classVariants = $this->getClassFileVariants( $filePath, $class );

		foreach ( $classVariants as $file ) {
			$file = strtolower( $file );
			if ( file_exists( $file ) && is_readable( $file ) ) {
				require_once $file;
				$this->loadedClasses[ $file ] = $className;

				return true;
			}
		}

		return false;
	}

	/**
	 * Generates possible file paths for class loading.
	 *
	 * @param string $filePath The base file path.
	 * @param string $class    The class name.
	 *
	 * @return array<string> List of possible file paths.
	 */
	private function getClassFileVariants( string $filePath, string $class ): array {
		$wpClass = str_replace( '_', '-', $class );

		return [
			"$filePath/$class.php",
			"$filePath/class-$class.php",
			"$filePath/interface-$class.php",
			"$filePath/trait-$class.php",
			"$filePath/$wpClass.php",
			"$filePath/class-$wpClass.php",
			"$filePath/interface-$wpClass.php",
			"$filePath/trait-$wpClass.php",
		];
	}
}

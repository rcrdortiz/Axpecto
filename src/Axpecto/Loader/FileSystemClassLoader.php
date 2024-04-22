<?php

namespace Axpecto\Loader;

use Axpecto\Container\Annotation\Singleton;
use Exception;
use InvalidArgumentException;

#[Singleton]
class FileSystemClassLoader {

	public function __construct(
		private array $registeredPaths = [],
		private array $loadedClasses = [],
	) {
		spl_autoload_register( $this->loadClass( ... ) );
	}

	public function registerPath( string $namespace, string $path ): static {
		$this->registeredPaths[ $namespace ] = $path;

		return $this;
	}

	/**
	 * @return array<string>
	 */
	public function get_registered_paths(): array {
		return $this->registeredPaths;
	}

	/**
	 * @throws Exception
	 */
	public function load_class_in_file( string $filename ): ?string {
		if ( isset( $this->loadedClasses[ strtolower( $filename ) ] ) ) {
			return $this->loadedClasses[ strtolower( $filename ) ];
		}

		if ( ! file_exists( $filename ) || ! is_readable( $filename ) ) {
			throw new InvalidArgumentException( "Could not load file: $filename" );
		}

		// @TODO Maybe add a wrapper so that we can have unit tests.
		$classes_before_require = array_merge( get_declared_classes(), get_declared_interfaces(), get_declared_traits() );
		ob_start();
		require_once $filename;
		ob_clean();

		$classes_after_require = array_merge( get_declared_classes(), get_declared_interfaces(), get_declared_traits() );
		$new_classes           = array_diff( $classes_after_require, $classes_before_require );
		$new_class             = array_shift( $new_classes );

		if ( $new_class ) {
			$this->loadedClasses[ $filename ] = $new_classes;

			return $new_class;
		}

		return null;
	}

	public function loadClass( string $class_name ): bool {
		$parts = explode( '\\', str_replace( '_', '-', $class_name ) );
		if ( count( $parts ) < 2 ) {
			return false;
		}

		$class     = array_pop( $parts );
		$namespace = array_shift( $parts );

		$base_path = $this->registeredPaths[ $namespace ] ?? null;
		if ( ! $base_path ) {
			return false;
		}

		$file_path = $base_path . join( '/', $parts );
		$files     = [
			$file_path . "/$class.php",
			$file_path . "/class-$class.php",
			$file_path . "/interface-$class.php",
			$file_path . "/trait-$class.php",
		];

		foreach ( $files as $file ) {
			$file = strtolower( $file );
			if ( file_exists( $file ) && is_readable( $file ) ) {
				require_once $file;
				$this->loadedClasses[ $file ] = $class_name;

				return true;
			}
		}

		return false;
	}
}
<?php

namespace Axpecto\Container\Exception;

use RuntimeException;
use Throwable;

/**
 * Class AutowireDependencyException
 *
 * Exception thrown when a failure occurs during autowiring of a dependency in the container.
 */
class AutowireDependencyException extends RuntimeException {
	/**
	 * Constructs the AutowireDependencyException.
	 *
	 * @param string         $class    The class where the autowiring failed.
	 * @param string         $type     The dependency type (class or value) that could not be autowired.
	 * @param Throwable|null $previous An optional previous exception for chaining.
	 */
	public function __construct( string $class, string $type, ?Throwable $previous = null ) {
		$message = sprintf(
			"An error occurred while trying to autowire the dependency: '%s' in the constructor of class '%s'. " .
			"If the dependency is a class, ensure that it exists and can be resolved. If it’s a value, verify that " .
			"it’s properly loaded in the container and the correct name is being used.",
			$type,
			$class
		);

		parent::__construct( $message, 1, $previous );
	}
}

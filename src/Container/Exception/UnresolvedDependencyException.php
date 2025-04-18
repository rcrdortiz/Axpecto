<?php

namespace Axpecto\Container\Exception;

use RuntimeException;

/**
 * Class UnresolvedDependencyException
 *
 * Exception thrown when a requested dependency cannot be resolved by the container.
 */
class UnresolvedDependencyException extends RuntimeException {
	/**
	 * Constructs the UnresolvedDependencyException.
	 *
	 * @param string $dependency The name of the unresolved dependency.
	 */
	public function __construct( string $dependency ) {
		$message = sprintf( 'Unresolved dependency: %s. Ensure the dependency is registered or can be autowired.', $dependency );
		parent::__construct( $message );
	}
}

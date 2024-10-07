<?php

namespace Axpecto\Container\Exception;

use RuntimeException;

/**
 * Class CircularReferenceException
 *
 * Exception thrown when a circular reference is detected during dependency injection.
 */
class CircularReferenceException extends RuntimeException {
	/**
	 * Constructs the CircularReferenceException.
	 *
	 * @param string $class The class where the circular reference was detected.
	 */
	public function __construct( string $class ) {
		$message = sprintf( 'Circular reference detected for class: %s. This class depends on itself or has a circular dependency chain.',
		                    $class );
		parent::__construct( $message );
	}
}

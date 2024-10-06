<?php

namespace Axpecto\Container\Exception;

use RuntimeException;

class AutowireDependencyException extends RuntimeException {
	public function __construct( string $class, string $type, $previous = null ) {
		parent::__construct(
			message:  "An error occurred while trying to autowire the dependency: $type in the constructor of class $class.If the dependency is a class, ensure that it exists. If it’s a value, verify that it’s properly loaded in the container and that the correct name is being used.",
			code:     1,
			previous: $previous
		);
	}
}
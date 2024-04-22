<?php

namespace Axpecto\Container\Exception;

use RuntimeException;

class CircularReferenceException extends RuntimeException {
	public function __construct( string $class ) {
		parent::__construct( "Circular reference detected for class $class" );
	}
}
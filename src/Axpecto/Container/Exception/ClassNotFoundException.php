<?php

namespace Axpecto\Container\Exception;
use RuntimeException;

class ClassNotFoundException extends RuntimeException {
	public function __construct( string $class, array $wiredClasses ) {
		$pretty = print_r( $wiredClasses, true );
		parent::__construct( "Could not find class '$class'. \nAuto wire trace: \n$pretty\n" );
	}
}

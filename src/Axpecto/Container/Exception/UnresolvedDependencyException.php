<?php

namespace Axpecto\Container\Exception;

use RuntimeException;

class UnresolvedDependencyException extends RuntimeException {
	public function __construct( string $dependency ) {
		parent::__construct( "Could not find dependency $dependency" );
	}
}
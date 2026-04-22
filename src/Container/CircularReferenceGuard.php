<?php
declare( strict_types=1 );

namespace Axpecto\Container;

use Axpecto\Container\Exception\CircularReferenceException;

/**
 * Throws if you re‑enter the same class during autowiring.
 */
class CircularReferenceGuard {
	/** @var array<string,bool> */
	private array $stack = [];

	public function enter( string $class ): void {
		if ( isset( $this->stack[ $class ] ) ) {
			throw new CircularReferenceException( $class );
		}
		$this->stack[ $class ] = true;
	}

	public function leave( string $class ): void {
		unset( $this->stack[ $class ] );
	}
}

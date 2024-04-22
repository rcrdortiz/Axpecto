<?php

namespace Axpecto\Collection\Abstract\Iterable;

abstract class Mutable extends Immutable {

	public function offsetSet( $offset, $value ): void {
		$this->array[$offset] = $value;
	}

	public function offsetUnset( $offset ): void {
		unset( $this->array[ $offset ] );
	}
}
<?php

namespace Axpecto\Collection\Concrete;

class MutableKmap extends Kmap {

	public function add( string $key, mixed $value ): static {
		$this->array[ $key ] = $value;
		$this->keys[] = $key;

		return $this;
	}

	public function get( string $key ): mixed {
		return $this->array[ $key ] ?? null;
	}

	public function merge( Kmap $map ): static {
		$newInternalMap = array_merge( $map->toArray(), $this->toArray() );
		$this->array = $newInternalMap;
		$this->keys = array_keys( $newInternalMap );
		$this->rewind();

		return $this;
	}
}
<?php

namespace Axpecto\Collection\Concrete;

use Closure;
use Axpecto\Collection\Abstract\Iterable\Immutable;

class Klist extends Immutable {

	public function flatten(): Klist {
		return new static( $this->toFlatArray() );
	}

	/**
	 * @TODO This should be to flat map, I just need this to preserve keys when flatting out.
	 */
	public function toFlatArray(): array {
		$data = [];
		foreach ( $this->array as $element ) {
			if ( is_array( $element ) ) {
				$data = array_merge( $data, $element );
			} elseif ( $element instanceof Klist ) {
				$data = array_merge( $data, $element->toArray() );
			} else {
				$data[] = $element;
			}
		}

		return $data;
	}

	public function map( Closure $transform ): Klist {
		$data = $this->toArray();

		foreach ( $data as &$element ) {
			$element = $transform( $element );
		}

		return new static( $data );
	}

	public function foreach( Closure $action ): Klist {
		$data = $this->toArray();
		foreach ( $data as $element ) {
			$action( $element );
		}

		return new static( $data );
	}

	public function filter( Closure $predicate ): static {
		$data = [];
		foreach ( $this->toArray() as $element ) {
			if ( $element instanceof Immutable ) {
				$element = $element->toArray();
			}

			if ( $predicate( $element ) ) {
				$data[] = $element;
			}
		}

		return new static( $data );
	}

	public function any( Closure $predicate ): bool {
		foreach ( $this->toArray() as $element ) {
			if ( $predicate( $element ) ) {
				return true;
			}
		}

		return false;
	}

	public function isNotEmpty(): bool {
		return count( $this ) > 0;
	}

	public function isEmpty(): bool {
		return count( $this ) === 0;
	}

	/*
	public function group_by( Closure $key_selector, ?Closure $value_transform = null ): KList {
		$grouped_data = [];
		$data         = $this->>array;
		foreach ( $data as $key => $value ) {
			if ( $value_transform ) {
				$value = $value_transform( $value );
			}

			$grouped_data[ $key_selector( $key, $value ) ][] = $value;
		}

		return mapOf( ...$grouped_data );
	}
	*/

	public function filterNotNull(): static {
		return $this->filter( fn( $element ) => $element );
	}

	public function merge( $value ): Klist {
		$data = array_merge( $value, $this->toArray() );

		return new static( $data );
	}

	public function add( mixed $element ) {
		return $this->merge( [ $element ] );
	}

	public function diff( Klist $list ): Klist {
		$data = $this->toArray();

		$data = array_diff( $data, $list->toArray() );

		return new static( $data );
	}

	public function join( string $separator ) {
		return join( $separator, $this->toArray() );
	}

	public function firstOrNull() {
		return $this->array[0] ?? null;
	}

	public function remove( mixed $to_remove ): Klist {
		return $this->filter( fn( $item ) => $item != $to_remove );
	}

	public function toMutable(): MutableKlist {
		return new MutableKlist( $this->toArray() );
	}
}
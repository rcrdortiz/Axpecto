<?php

namespace Axpecto\Collection\Concrete;

use Closure;
use Axpecto\Collection\Abstract\Iterable\Immutable;
use Axpecto\Collection\Abstract\Iterable\Mutable;

class MutableKlist extends Mutable {

	public function flatten(): MutableKlist {
		$this->array = $this->toFlatArray();

		return $this;
	}

	/**
	 * @TODO This should be to flat map, I just need this to preserve keys when flatting out.
	 */
	public function toFlatArray(): array {
		$data = [];
		foreach ( $this->array as $element ) {
			if ( is_array( $element ) ) {
				$data = array_merge( $data, $element );
			} elseif ( $element instanceof MutableKlist ) {
				$data = array_merge( $data, $element->toArray() );
			} else {
				$data[] = $element;
			}
		}

		return $data;
	}

	public function map( Closure $transform ): MutableKlist {
		foreach ( $this->array as $index => $element ) {
			$this->array[ $index ] = $transform( $element );
		}

		return $this;
	}

	public function foreach( Closure $action ): MutableKlist {
		foreach ( $this->array as $element ) {
			$action( $element );
		}

		return $this;
	}

	public function filter( Closure $predicate ): static {
		foreach ( $this->array as $index => $element ) {
			if ( $element instanceof Immutable ) {
				$element = $element->toArray();
			}

			if ( ! $predicate( $element ) ) {
				unset( $this->array[ $index ] );
			}
		}

		return $this;
	}

	public function any( Closure $predicate ): bool {
		foreach ( $this->array as $element ) {
			if ( $predicate( $element ) ) {
				return true;
			}
		}

		return false;
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

	public function merge( $value ): MutableKlist {
		$this->array = array_merge( $value, $this->toArray() );

		return $this;
	}

	public function add( mixed $element ) {
		$this->array[] = $element;

		return $this;
	}

	public function diff( Immutable $list ): MutableKlist {
		$this->array = array_diff( $this->array, $list->toArray() );

		return $this;
	}

	public function join( string $separator ) {
		return join( $separator, $this->toArray() );
	}

	public function first_or_null() {
		return $this->array[0] ?? null;
	}

	public function remove( mixed $to_remove ): MutableKlist {
		return $this->filter( fn( $item ) => $item != $to_remove );
	}

	public function as_read_only(): Klist {
		return new Klist( $this->toArray() );
	}
}
<?php

namespace Axpecto\Collection\Concrete;

use Closure;
use Axpecto\Collection\Abstract\Iterable\Immutable;

class Kmap extends Immutable {

	/**
	 * @var array<string>
	 */
	protected array $keys;

	public function __construct(
		array $array = [],
	) {
		parent::__construct( $array );
		$this->keys = array_keys( $array );
	}

	public function current(): mixed {
		return $this->array[ $this->keys[ $this->index ] ];
	}

	public function has( string $key ): bool {
		return isset( $this->array[ $key ] );
	}

	public function search( mixed $value ): string {
		return array_search( $value, $this->array, true );
	}

	public function key(): int {
		return $this->keys[ $this->index ];
	}

	public function valid(): bool {
		return isset( $this->array[ $this->keys[ $this->index ] ] );
	}

	public function filter( Closure $predicate ): static {
		$data = [];
		foreach ( $this->toArray() as $key => $element ) {
			if ( $element instanceof Immutable ) {
				$element = $element->toArray();
			}

			if ( $predicate( $key, $element ) ) {
				$data[ $key ] = $element;
			}
		}

		return new static( $data );
	}

	public function map( Closure $transform ): Kmap {
		$data = $this->toArray();

		foreach ( $data as $key => &$element ) {
			$element = $transform( $key, $element );
		}

		return new static( $data );
	}

	public function mapOf( Closure $transform ): KMap {
		$data = array();
		foreach ( $this->array as $key => $value ) {
			$entry = $transform( $key, $value );

			$data[key( $entry)] = current( $entry );
		}

		return mapOf( $data );
	}

	public function foreach( Closure $action ) {
		foreach ( $this->array as $key => $element ) {
			$action( $key, $element );
		}

		return $this;
	}

	public function merge( Kmap $map ) {
		return new static( array_merge( $this->toArray(), $map->toArray() ) );
	}

	public function any( Closure $predicate ): bool {
		foreach ( $this->toArray() as $key => $element ) {
			if ( $predicate( $key, $element ) ) {
				return true;
			}
		}

		return false;
	}

	public function all( Closure $predicate ): bool {
		foreach ( $this->toArray() as $key => $element ) {
			if ( ! $predicate( $key, $element ) ) {
				return false;
			}
		}

		return true;
	}

	public function filterNotNull(): static {
		return $this->filter( fn( $index, $element ) => $element );
	}

	public function firstOrNull() {
		return $this->array[ $this->keys[0] ?? 0 ] ?? null;
	}

	public function maybe( Closure $param ) {
		$param( $this->array );

		return $this;
	}
}
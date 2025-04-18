<?php

namespace Axpecto\Collection;

use Closure;
use Exception;

class Kmap implements CollectionInterface {
	protected array $keys;
	protected array $array = [];
	protected int $index = 0;

	public function __construct(
		array $array = [],
		private readonly bool $mutable = false
	) {
		$this->keys  = array_keys( $array );
		$this->array = $array;
	}

	public function current(): mixed {
		return $this->valid() ? $this->array[$this->index] : null;
	}

	public function key(): mixed {
		return $this->keys[ $this->index ] ?? null;
	}

	public function next(): void {
		$this->index ++;
	}

	public function valid(): bool {
		return isset( $this->keys[ $this->index ] ) && isset( $this->array[ $this->keys[ $this->index ] ] );
	}

	public function rewind(): void {
		$this->index = 0;
	}

	public function offsetExists( $offset ): bool {
		return isset( $this->array[ $offset ] );
	}

	public function offsetGet( $offset ): mixed {
		return $this->array[ $offset ];
	}

	public function offsetSet( $offset, $value ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Immutable collection cannot be modified" );
		}

		$this->array[ $offset ] = $value;
		$this->keys             = array_keys( $this->array );
	}

	public function offsetUnset( $offset ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Immutable collection cannot be modified" );
		}

		unset( $this->array[ $offset ] );
		$this->keys = array_keys( $this->array );
	}

	public function count(): int {
		return count( $this->array );
	}

	public function isEmpty(): bool {
		return count( $this->array ) === 0;
	}

	public function filterNotNull(): static {
		return $this->filter( fn( $element ) => $element !== null );
	}

	public function any( Closure $predicate ): bool {
		foreach ( $this->array as $element ) {
			if ( $predicate( $element ) ) {
				return true;
			}
		}

		return false;
	}

	public function all( Closure $predicate ): bool {
		foreach ( $this->array as $element ) {
			if ( ! $predicate( $element ) ) {
				return false;
			}
		}

		return true;
	}

	public function isNotEmpty(): bool {
		return count( $this->array ) > 0;
	}

	public function jsonSerialize(): string {
		return json_encode( $this->array );
	}

	public function toArray(): array {
		return $this->array;
	}

	public function filter( Closure $predicate ): static {
		$filtered = [];
		foreach ( $this->array as $key => $value ) {
			if ( $predicate( $key, $value ) ) {
				$filtered[ $key ] = $value;
			}
		}

		if ( $this->mutable ) {
			$this->array = $filtered;
			$this->keys  = array_keys( $filtered );

			return $this;
		}

		return new static( $filtered );
	}

	public function mergeArray( array $array ): static {
		$data = array_merge( $array, $this->toArray() );

		if ( $this->mutable ) {
			$this->array = $data;
			$this->keys  = array_keys( $data );

			return $this;
		}

		return new static( $data );
	}

	public function merge( CollectionInterface $map ): static {
		return $this->mergeArray( $map->toArray() );
	}

	public function map( Closure $transform ): static {
		$data = array();
		foreach ( $this->array as $key => $value ) {
			$entry = $transform( $key, $value );

			$data[ key( $entry ) ] = current( $entry );
		}

		if ( $this->mutable ) {
			$this->array = $data;
			$this->keys  = array_keys( $data );

			return $this;
		}

		return mapOf( $data );
	}

	/**
	 * @throws Exception
	 */
	public function join( string $separator ): string {
		if ( $this->any( fn( $element ) => ! is_string( $element ) ) ) {
			throw new Exception( "Cannot join non-string elements" );
		}

		return join( $separator, $this->toArray() );
	}

	public function maybe( Closure $predicate ): static {
		count( $this ) > 0 && $predicate( $this );

		return $this;
	}

	public function toMutable(): static {
		if ( $this->mutable ) {
			return $this;
		}

		return new static( $this->array, true );
	}

	public function toImmutable(): static {
		if ( ! $this->mutable ) {
			return $this;
		}

		return new static( $this->array, false );
	}

	public function flatten(): static {
		$data = [];
		foreach ( $this->array as $element ) {
			if ( is_array( $element ) ) {
				$data = array_merge( $data, $element );
			} elseif ( $element instanceof CollectionInterface ) {
				$data = array_merge( $data, $element->toArray() );
			} else {
				$data[] = $element;
			}
		}

		if ( $this->mutable ) {
			$this->array = $data;
			$this->keys  = array_keys( $data );

			return $this;
		}

		return new static( $data );
	}

	public function foreach( Closure $transform ): static {
		$data = $this->toArray();
		foreach ( $data as $key => $element ) {
			$transform( $key, $element );
		}

		return $this;
	}

	public function firstOrNull(): mixed {
		return $this->array[ $this->keys[0] ?? 0 ] ?? null;
	}

	public function add( $key, mixed $element ) {
		$this->offsetSet( $key, $element );
	}

	public function resetKeys() {
		$this->array = array_values( $this->array );
		$this->keys  = array_keys( $this->array );
	}
}

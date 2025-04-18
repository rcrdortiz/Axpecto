<?php

namespace Axpecto\Collection;

use Closure;
use Exception;

/**
 * A key-value based collection that supports functional operations.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements CollectionInterface<TKey, TValue>
 */
class Kmap implements CollectionInterface {
	/** @var array<TKey> */
	protected array $keys;

	/** @var array<TKey, TValue> */
	protected array $array = [];

	protected int $index = 0;

	/**
	 * @param array<TKey, TValue> $array
	 * @param bool                $mutable
	 */
	public function __construct( array $array = [], private readonly bool $mutable = false ) {
		$this->keys  = array_keys( $array );
		$this->array = $array;
	}

	public function current(): mixed {
		return $this->valid() ? $this->array[ $this->keys[ $this->index ] ] : null;
	}

	public function key(): mixed {
		return $this->keys[ $this->index ] ?? null;
	}

	public function next(): void {
		$this->index ++;
	}

	public function valid(): bool {
		return isset( $this->keys[ $this->index ] ) && array_key_exists( $this->keys[ $this->index ], $this->array );
	}

	public function rewind(): void {
		$this->index = 0;
	}

	public function offsetExists( mixed $offset ): bool {
		return isset( $this->array[ $offset ] );
	}

	public function offsetGet( mixed $offset ): mixed {
		return $this->array[ $offset ];
	}

	public function offsetSet( mixed $offset, mixed $value ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Immutable collection cannot be modified" );
		}

		/** @psalm-suppress MixedAssignment */
		$this->array[ $offset ] = $value;
		$this->keys             = array_keys( $this->array );
	}

	public function offsetUnset( mixed $offset ): void {
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

	public function isNotEmpty(): bool {
		return ! $this->isEmpty();
	}

	/**
	 * @return static
	 */
	public function filterNotNull(): static {
		return $this->filter( fn( $k, $v ) => $v !== null );
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	public function any( Closure $predicate ): bool {
		foreach ( $this->array as $element ) {
			if ( $predicate( $element ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	public function all( Closure $predicate ): bool {
		foreach ( $this->array as $element ) {
			if ( ! $predicate( $element ) ) {
				return false;
			}
		}

		return true;
	}

	public function jsonSerialize(): mixed {
		return $this->array;
	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function toArray(): array {
		return $this->array;
	}

	/**
	 * @param Closure(TKey, TValue):bool $predicate
	 *
	 * @return static
	 */
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

	/**
	 * @param array<TKey, TValue> $array
	 *
	 * @return static
	 */
	public function mergeArray( array $array ): static {
		$data = array_merge( $array, $this->array );

		if ( $this->mutable ) {
			$this->array = $data;
			$this->keys  = array_keys( $data );

			return $this;
		}

		return new static( $data );
	}

	/**
	 * @param CollectionInterface<TKey, TValue> $collection
	 *
	 * @return static
	 */
	public function merge( CollectionInterface $collection ): static {
		return $this->mergeArray( $collection->toArray() );
	}

	/**
	 * @template TMap
	 * @param Closure(TKey, TValue):array{0:TKey, 1:TMap} $transform
	 *
	 * @return Kmap<TKey, TMap>
	 */
	public function map( Closure $transform ): Kmap {
		$data = [];

		foreach ( $this->array as $key => $value ) {
			[ $newKey, $newValue ] = $transform( $key, $value );
			$data[ $newKey ] = $newValue;
		}

		if ( $this->mutable ) {
			$this->array = $data;
			$this->keys  = array_keys( $data );

			return $this;
		}

		return new Kmap( $data );
	}

	/**
	 * @throws Exception
	 */
	public function join( string $separator ): string {
		if ( $this->any( fn( $element ) => ! is_string( $element ) ) ) {
			throw new Exception( "Cannot join non-string elements" );
		}

		return join( $separator, $this->array );
	}

	/**
	 * @param Closure(self):void $predicate
	 *
	 * @return static
	 */
	public function maybe( Closure $predicate ): static {
		if ( $this->count() > 0 ) {
			$predicate( $this );
		}

		return $this;
	}

	/**
	 * @return static
	 */
	public function toMutable(): static {
		return $this->mutable ? $this : new static( $this->array, true );
	}

	/**
	 * @return static
	 */
	public function toImmutable(): static {
		return ! $this->mutable ? $this : new static( $this->array, false );
	}

	/**
	 * @return static
	 */
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

	/**
	 * @param Closure(TKey, TValue):void $transform
	 *
	 * @return static
	 */
	public function foreach( Closure $transform ): static {
		foreach ( $this->array as $key => $element ) {
			$transform( $key, $element );
		}

		return $this;
	}

	/**
	 * @return TValue|null
	 */
	public function firstOrNull(): mixed {
		return $this->array[ $this->keys[0] ?? 0 ] ?? null;
	}

	/**
	 * @param TKey   $key
	 * @param TValue $element
	 *
	 * @return void
	 * @throws Exception
	 */
	public function add( $key, mixed $element ): void {
		$this->offsetSet( $key, $element );
	}

	/**
	 * Reset keys to numeric indexes (for use after filtering).
	 *
	 * @return void
	 */
	public function resetKeys(): void {
		$this->array = array_values( $this->array );
		$this->keys  = array_keys( $this->array );
	}
}

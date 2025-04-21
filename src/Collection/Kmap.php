<?php

namespace Axpecto\Collection;

use Closure;
use Exception;
use Override;

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
	 * @param bool $mutable
	 */
	public function __construct( array $array = [], private readonly bool $mutable = false ) {
		$this->keys  = array_keys( $array );
		$this->array = $array;
	}

	#[Override]
	public function current(): mixed {
		return $this->valid() ? $this->array[ $this->keys[ $this->index ] ] : null;
	}

	#[Override]
	public function key(): mixed {
		return $this->keys[ $this->index ] ?? null;
	}

	#[Override]
	public function next(): void {
		$this->index ++;
	}

	/**
	 * Returns the current item and advances the internal pointer.
	 *
	 * @return TValue|null
	 */
	#[Override]
	public function nextAndGet(): mixed {
		$current = $this->current();
		$this->next();

		return $current;
	}

	#[Override]
	public function valid(): bool {
		return isset( $this->keys[ $this->index ] ) && array_key_exists( $this->keys[ $this->index ], $this->array );
	}

	#[Override]
	public function rewind(): void {
		$this->index = 0;
	}

	#[Override]
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->array[ $offset ] );
	}

	#[Override]
	public function offsetGet( mixed $offset ): mixed {
		return $this->array[ $offset ];
	}

	/**
	 * @throws Exception
	 */
	#[Override]
	public function offsetSet( mixed $offset, mixed $value ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Immutable collection cannot be modified" );
		}

		/** @psalm-suppress MixedAssignment */
		$this->array[ $offset ] = $value;
		$this->keys             = array_keys( $this->array );
	}

	/**
	 * @throws Exception
	 */
	#[Override]
	public function offsetUnset( mixed $offset ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Immutable collection cannot be modified" );
		}

		unset( $this->array[ $offset ] );
		$this->keys = array_keys( $this->array );
	}

	#[Override]
	public function count(): int {
		return count( $this->array );
	}

	#[Override]
	public function isEmpty(): bool {
		return count( $this->array ) === 0;
	}

	#[Override]
	public function isNotEmpty(): bool {
		return ! $this->isEmpty();
	}

	/**
	 * @return static
	 */
	#[Override]
	public function filterNotNull(): static {
		return $this->filter( fn( $k, $v ) => $v !== null );
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	#[Override]
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
	#[Override]
	public function all( Closure $predicate ): bool {
		foreach ( $this->array as $element ) {
			if ( ! $predicate( $element ) ) {
				return false;
			}
		}

		return true;
	}

	#[Override]
	public function jsonSerialize(): mixed {
		return $this->array;
	}

	/**
	 * @return array<TKey, TValue>
	 */
	#[Override]
	public function toArray(): array {
		return $this->array;
	}

	/**
	 * @param Closure(TKey, TValue):bool $predicate
	 *
	 * @return static
	 */
	#[Override]
	public function filter( Closure $predicate ): static {
		$filtered = array_filter( $this->array, function ( $value, $key ) use ( $predicate ) {
			return $predicate( $key, $value );
		}, ARRAY_FILTER_USE_BOTH );

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
	#[Override]
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
	 * @psalm-suppress PossiblyUnusedReturnValue
	 *
	 * @return static
	 */
	#[Override]
	public function merge( CollectionInterface $collection ): static {
		return $this->mergeArray( $collection->toArray() );
	}

	/**
	 * @template TMapKey of array-key
	 * @template TMapValue
	 * @param Closure(TKey, TValue): array<TMapKey, TMapValue> $transform
	 *
	 * @return Kmap<TMapKey, TMapValue>
	 */
	#[Override]
	public function map( Closure $transform ): Kmap {
		$data = [];

		foreach ( $this->array as $key => $value ) {
			$entry                 = $transform( $key, $value );
			$data[ key( $entry ) ] = current( $entry );
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
	#[Override]
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
	#[Override]
	public function maybe( Closure $predicate ): static {
		if ( $this->count() > 0 ) {
			$predicate( $this );
		}

		return $this;
	}

	/**
	 * @return static
	 */
	#[Override]
	public function toMutable(): static {
		return $this->mutable ? $this : new static( $this->array, true );
	}

	/**
	 * @return static
	 */
	#[Override]
	public function toImmutable(): static {
		return ! $this->mutable ? $this : new static( $this->array, false );
	}

	/**
	 * @return static
	 */
	#[Override]
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
	#[Override]
	public function foreach( Closure $transform ): static {
		foreach ( $this->array as $key => $element ) {
			$transform( $key, $element );
		}

		return $this;
	}

	/**
	 * @return TValue|null
	 */
	#[Override]
	public function firstOrNull(): mixed {
		return $this->array[ $this->keys[0] ?? 0 ] ?? null;
	}

	/**
	 * @param TKey $key
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

	/**
	 * Reduce the collection to a single value.
	 *
	 * @param Closure(mixed, TValue):mixed $transform
	 * @param mixed|null $initial
	 *
	 * @return mixed
	 */
	#[Override]
	public function reduce( Closure $transform, mixed $initial = null ): mixed {
		$carry = $initial;
		foreach ( $this->array as $element ) {
			$carry = $transform( $carry, $element );
		}

		return $carry;
	}
}

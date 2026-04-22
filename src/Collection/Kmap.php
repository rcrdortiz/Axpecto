<?php

namespace Axpecto\Collection;

use Closure;
use Exception;
use Override;

/**
 * A key-value map collection with functional operations.
 *
 * Drops the redundant $keys array from the previous implementation.
 * Iterator keys are built lazily on first iteration and invalidated on mutation.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements CollectionInterface<TKey, TValue>
 */
class Kmap implements CollectionInterface {
	private int $iteratorIndex = 0;
	private ?array $iteratorKeys = null;

	/**
	 * @param array<TKey, TValue> $data
	 * @param bool $mutable
	 */
	public function __construct(
		protected array $data = [],
		private readonly bool $mutable = false,
	) {
	}

	// ---------------------------------------------------------------
	// Iterator
	// ---------------------------------------------------------------

	#[Override]
	public function current(): mixed {
		$keys = $this->iteratorKeys();
		return isset( $keys[ $this->iteratorIndex ] )
			? $this->data[ $keys[ $this->iteratorIndex ] ]
			: null;
	}

	#[Override]
	public function key(): mixed {
		return $this->iteratorKeys()[ $this->iteratorIndex ] ?? null;
	}

	#[Override]
	public function next(): void {
		$this->iteratorIndex++;
	}

	#[Override]
	public function nextAndGet(): mixed {
		$current = $this->current();
		$this->next();

		return $current;
	}

	#[Override]
	public function valid(): bool {
		$keys = $this->iteratorKeys();

		return isset( $keys[ $this->iteratorIndex ] )
		       && array_key_exists( $keys[ $this->iteratorIndex ], $this->data );
	}

	#[Override]
	public function rewind(): void {
		$this->iteratorIndex = 0;
		$this->iteratorKeys  = null;
	}

	private function iteratorKeys(): array {
		return $this->iteratorKeys ??= array_keys( $this->data );
	}

	private function invalidateIterator(): void {
		$this->iteratorKeys = null;
	}

	// ---------------------------------------------------------------
	// ArrayAccess
	// ---------------------------------------------------------------

	#[Override]
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->data[ $offset ] );
	}

	#[Override]
	public function offsetGet( mixed $offset ): mixed {
		return $this->data[ $offset ];
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
		$this->data[ $offset ] = $value;
		$this->invalidateIterator();
	}

	/**
	 * @throws Exception
	 */
	#[Override]
	public function offsetUnset( mixed $offset ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Immutable collection cannot be modified" );
		}

		unset( $this->data[ $offset ] );
		$this->invalidateIterator();
	}

	// ---------------------------------------------------------------
	// Countable & JsonSerializable
	// ---------------------------------------------------------------

	#[Override]
	public function count(): int {
		return count( $this->data );
	}

	#[Override]
	public function jsonSerialize(): mixed {
		return $this->data;
	}

	// ---------------------------------------------------------------
	// Core reads
	// ---------------------------------------------------------------

	/**
	 * @return array<TKey, TValue>
	 */
	#[Override]
	public function toArray(): array {
		return $this->data;
	}

	#[Override]
	public function isEmpty(): bool {
		return count( $this->data ) === 0;
	}

	#[Override]
	public function isNotEmpty(): bool {
		return ! $this->isEmpty();
	}

	/**
	 * @return TValue|null
	 */
	#[Override]
	public function firstOrNull(): mixed {
		if ( empty( $this->data ) ) {
			return null;
		}

		return reset( $this->data );
	}

	// ---------------------------------------------------------------
	// Functional — filter
	// ---------------------------------------------------------------

	/**
	 * Filter entries by a predicate receiving (key, value).
	 *
	 * @param Closure(TKey, TValue): bool $predicate
	 *
	 * @return static
	 */
	#[Override]
	public function filter( Closure $predicate ): static {
		$filtered = [];
		foreach ( $this->data as $key => $value ) {
			if ( $predicate( $key, $value ) ) {
				$filtered[ $key ] = $value;
			}
		}

		return $this->deriveOrMutate( $filtered );
	}

	#[Override]
	public function filterNotNull(): static {
		return $this->filter( fn( $k, $v ) => $v !== null );
	}

	/**
	 * Filter entries by key.
	 *
	 * @param Closure(TKey): bool $predicate
	 *
	 * @return static
	 */
	public function filterKeys( Closure $predicate ): static {
		return $this->filter( fn( $k, $v ) => $predicate( $k ) );
	}

	/**
	 * Filter entries by value.
	 *
	 * @param Closure(TValue): bool $predicate
	 *
	 * @return static
	 */
	public function filterValues( Closure $predicate ): static {
		return $this->filter( fn( $k, $v ) => $predicate( $v ) );
	}

	// ---------------------------------------------------------------
	// Functional — map
	// ---------------------------------------------------------------

	/**
	 * Transform values, preserving keys.
	 *
	 * @template TNewValue
	 * @param Closure(TKey, TValue): TNewValue $transform
	 *
	 * @return Kmap<TKey, TNewValue>
	 */
	#[Override]
	public function map( Closure $transform ): Kmap {
		$result = [];
		foreach ( $this->data as $key => $value ) {
			$result[ $key ] = $transform( $key, $value );
		}

		return $this->deriveOrMutate( $result );
	}

	/**
	 * Alias for map().
	 *
	 * @template TNewValue
	 * @param Closure(TKey, TValue): TNewValue $transform
	 *
	 * @return Kmap<TKey, TNewValue>
	 */
	public function mapValues( Closure $transform ): Kmap {
		return $this->map( $transform );
	}

	/**
	 * Transform keys, preserving values.
	 *
	 * @template TNewKey of array-key
	 * @param Closure(TKey, TValue): TNewKey $transform
	 *
	 * @return Kmap<TNewKey, TValue>
	 */
	public function mapKeys( Closure $transform ): Kmap {
		$result = [];
		foreach ( $this->data as $key => $value ) {
			$result[ $transform( $key, $value ) ] = $value;
		}

		return $this->deriveOrMutate( $result );
	}

	// ---------------------------------------------------------------
	// Functional — predicates
	// ---------------------------------------------------------------

	/**
	 * @param Closure(TValue): bool $predicate
	 */
	#[Override]
	public function any( Closure $predicate ): bool {
		foreach ( $this->data as $element ) {
			if ( $predicate( $element ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Closure(TValue): bool $predicate
	 */
	#[Override]
	public function all( Closure $predicate ): bool {
		foreach ( $this->data as $element ) {
			if ( ! $predicate( $element ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * True if no element matches the predicate.
	 *
	 * @param Closure(TValue): bool $predicate
	 */
	public function none( Closure $predicate ): bool {
		return ! $this->any( $predicate );
	}

	// ---------------------------------------------------------------
	// Functional — iteration & reduction
	// ---------------------------------------------------------------

	/**
	 * @param Closure(TKey, TValue): void $transform
	 *
	 * @return static
	 */
	#[Override]
	public function foreach( Closure $transform ): static {
		foreach ( $this->data as $key => $value ) {
			$transform( $key, $value );
		}

		return $this;
	}

	/**
	 * Alias for foreach() — side-effect on each entry.
	 *
	 * @param Closure(TKey, TValue): void $action
	 *
	 * @return static
	 */
	public function onEach( Closure $action ): static {
		return $this->foreach( $action );
	}

	#[Override]
	public function reduce( Closure $transform, mixed $initial = null ): mixed {
		$carry = $initial;
		foreach ( $this->data as $value ) {
			$carry = $transform( $carry, $value );
		}

		return $carry;
	}

	/**
	 * @param Closure(TKey, TValue): int|float $selector
	 */
	public function sumOf( Closure $selector ): int|float {
		$sum = 0;
		foreach ( $this->data as $key => $value ) {
			$sum += $selector( $key, $value );
		}

		return $sum;
	}

	/**
	 * @param Closure(TKey, TValue): mixed $selector
	 *
	 * @return TValue|null
	 */
	public function minByOrNull( Closure $selector ): mixed {
		$minItem = null;
		$minSel  = null;
		foreach ( $this->data as $key => $value ) {
			$sel = $selector( $key, $value );
			if ( $minSel === null || $sel < $minSel ) {
				$minSel  = $sel;
				$minItem = $value;
			}
		}

		return $minItem;
	}

	/**
	 * @param Closure(TKey, TValue): mixed $selector
	 *
	 * @return TValue|null
	 */
	public function maxByOrNull( Closure $selector ): mixed {
		$maxItem = null;
		$maxSel  = null;
		foreach ( $this->data as $key => $value ) {
			$sel = $selector( $key, $value );
			if ( $maxSel === null || $sel > $maxSel ) {
				$maxSel  = $sel;
				$maxItem = $value;
			}
		}

		return $maxItem;
	}

	// ---------------------------------------------------------------
	// Functional — string / merge / flatten
	// ---------------------------------------------------------------

	/**
	 * @throws Exception
	 */
	#[Override]
	public function join( string $separator ): string {
		if ( $this->any( fn( $element ) => ! is_string( $element ) ) ) {
			throw new Exception( "Cannot join non-string elements" );
		}

		return implode( $separator, $this->data );
	}

	/**
	 * @param array<TKey, TValue> $array
	 *
	 * @return static
	 */
	#[Override]
	public function mergeArray( array $array ): static {
		return $this->deriveOrMutate( array_merge( $array, $this->data ) );
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

	#[Override]
	public function flatten(): static {
		$result = [];
		foreach ( $this->data as $element ) {
			if ( is_array( $element ) ) {
				$result = array_merge( $result, $element );
			} elseif ( $element instanceof CollectionInterface ) {
				$result = array_merge( $result, $element->toArray() );
			} else {
				$result[] = $element;
			}
		}

		return $this->deriveOrMutate( $result );
	}

	// ---------------------------------------------------------------
	// Conditional / conversion
	// ---------------------------------------------------------------

	/**
	 * @param Closure(self): void $predicate
	 *
	 * @return static
	 */
	#[Override]
	public function maybe( Closure $predicate ): static {
		if ( $this->isNotEmpty() ) {
			$predicate( $this );
		}

		return $this;
	}

	#[Override]
	public function toMutable(): static {
		return $this->mutable ? $this : new static( $this->data, true );
	}

	#[Override]
	public function toImmutable(): static {
		return ! $this->mutable ? $this : new static( $this->data, false );
	}

	// ---------------------------------------------------------------
	// Map-specific lookups
	// ---------------------------------------------------------------

	/**
	 * Get value by key, or return default.
	 *
	 * @param TKey $key
	 * @param TValue $default
	 *
	 * @return TValue
	 */
	public function getOrDefault( mixed $key, mixed $default ): mixed {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}

	/**
	 * Get value by key, or compute and store it (mutable only).
	 *
	 * @param TKey $key
	 * @param Closure(): TValue $defaultValue
	 *
	 * @return TValue
	 * @throws Exception
	 */
	public function getOrPut( mixed $key, Closure $defaultValue ): mixed {
		if ( array_key_exists( $key, $this->data ) ) {
			return $this->data[ $key ];
		}
		if ( ! $this->mutable ) {
			throw new Exception( "Cannot put into immutable map" );
		}
		$value                = $defaultValue();
		$this->data[ $key ]   = $value;
		$this->invalidateIterator();

		return $value;
	}

	public function containsKey( mixed $key ): bool {
		return array_key_exists( $key, $this->data );
	}

	public function containsValue( mixed $value ): bool {
		return in_array( $value, $this->data, true );
	}

	/**
	 * @return Klist<TKey>
	 */
	public function keys(): Klist {
		return new Klist( array_keys( $this->data ) );
	}

	/**
	 * @return Klist<TValue>
	 */
	public function values(): Klist {
		return new Klist( array_values( $this->data ) );
	}

	/**
	 * @return Klist<array{TKey, TValue}>
	 */
	public function entries(): Klist {
		$entries = [];
		foreach ( $this->data as $key => $value ) {
			$entries[] = [ $key, $value ];
		}

		return new Klist( $entries );
	}

	/**
	 * @return Klist<TValue>
	 */
	public function toList(): Klist {
		return $this->values();
	}

	// ---------------------------------------------------------------
	// Mutation
	// ---------------------------------------------------------------

	/**
	 * @param TKey $key
	 * @param TValue $element
	 *
	 * @throws Exception
	 */
	public function add( $key, mixed $element ): void {
		$this->offsetSet( $key, $element );
	}

	/**
	 * @param TKey $key
	 *
	 * @throws Exception
	 */
	public function remove( mixed $key ): void {
		$this->offsetUnset( $key );
	}

	/**
	 * Reset keys to numeric indexes.
	 */
	public function resetKeys(): void {
		$this->data = array_values( $this->data );
		$this->invalidateIterator();
	}

	// ---------------------------------------------------------------
	// Internal
	// ---------------------------------------------------------------

	private function deriveOrMutate( array $data ): static {
		if ( $this->mutable ) {
			$this->data = $data;
			$this->invalidateIterator();

			return $this;
		}

		return new static( $data );
	}
}

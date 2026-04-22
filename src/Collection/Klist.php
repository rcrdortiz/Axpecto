<?php

namespace Axpecto\Collection;

use Closure;
use Exception;
use Override;

/**
 * A list collection with lazy pipeline evaluation.
 *
 * Intermediate operations (filter, map, flatMap, etc.) are deferred — they
 * append to an internal pipeline and return a new Klist without processing data.
 *
 * Terminal operations (toArray, firstOrNull, count, etc.) trigger evaluation.
 * Short-circuit terminals (firstOrNull, any) avoid full materialization when
 * the pipeline consists only of fuseable operations (filter, map, mapNotNull).
 *
 * @template TValue
 * @implements CollectionInterface<int, TValue>
 */
class Klist implements CollectionInterface {
	/** Pipeline step types that can be fused into a single per-element pass. */
	private const FUSEABLE = [ 'filter', 'map', 'mapNotNull', 'onEach' ];

	/** Sentinel object identity — used to mark filtered-out elements in the pipeline. */
	private static ?object $FILTERED = null;

	/** @var array<int, TValue>|null Cached result of pipeline evaluation. */
	private ?array $materialized = null;

	/** Iterator position within the materialized array. */
	private int $iteratorIndex = 0;

	/**
	 * @param array<int, TValue> $source   Raw source data.
	 * @param bool               $mutable  Whether mutation is allowed.
	 * @param array              $pipeline Deferred operation steps (internal).
	 */
	public function __construct(
		private readonly array $source = [],
		private readonly bool $mutable = false,
		private readonly array $pipeline = [],
	) {
		// Mutable lists are always eagerly materialized — no lazy deferral.
		if ( $mutable ) {
			$this->materialized = array_values( $source );
		}
	}

	private static function filtered(): object {
		return self::$FILTERED ??= new \stdClass();
	}

	// ===============================================================
	// Intermediate operations — lazy, return a new Klist
	// ===============================================================

	/**
	 * Keep only elements matching the predicate.
	 *
	 * @param Closure(TValue): bool $predicate
	 *
	 * @return static
	 */
	#[Override]
	public function filter( Closure $predicate ): static {
		return $this->pipe( 'filter', $predicate );
	}

	#[Override]
	public function filterNotNull(): static {
		return $this->pipe( 'filter', fn( $v ) => $v !== null );
	}

	/**
	 * Transform each element.
	 *
	 * @template TOut
	 * @param Closure(TValue): TOut $transform
	 *
	 * @return Klist<TOut>
	 */
	#[Override]
	public function map( Closure $transform ): static {
		return $this->pipe( 'map', $transform );
	}

	/**
	 * Transform elements, dropping null results.
	 *
	 * @template TOut
	 * @param Closure(TValue): (TOut|null) $transform
	 *
	 * @return Klist<TOut>
	 */
	public function mapNotNull( Closure $transform ): static {
		return $this->pipe( 'mapNotNull', $transform );
	}

	/**
	 * Transform each element to an iterable and flatten the results.
	 *
	 * @template TOut
	 * @param Closure(TValue): iterable<TOut> $transform
	 *
	 * @return Klist<TOut>
	 */
	public function flatMap( Closure $transform ): static {
		return $this->pipe( 'flatMap', $transform );
	}

	/**
	 * Flatten one level of nesting.
	 *
	 * @return static
	 */
	#[Override]
	public function flatten(): static {
		return $this->pipe( 'flatten', null );
	}

	/**
	 * Take the first $n elements.
	 */
	public function take( int $n ): static {
		return $this->pipe( 'take', $n );
	}

	/**
	 * Skip the first $n elements.
	 */
	public function drop( int $n ): static {
		return $this->pipe( 'drop', $n );
	}

	/**
	 * Remove duplicate values (by strict equality).
	 */
	public function distinct(): static {
		return $this->pipe( 'distinct', null );
	}

	/**
	 * Remove duplicates by a key selector.
	 *
	 * @param Closure(TValue): mixed $selector
	 */
	public function distinctBy( Closure $selector ): static {
		return $this->pipe( 'distinctBy', $selector );
	}

	/**
	 * Sort ascending by a selector.
	 *
	 * @param Closure(TValue): mixed $selector
	 */
	public function sortedBy( Closure $selector ): static {
		return $this->pipe( 'sortedBy', $selector );
	}

	/**
	 * Sort descending by a selector.
	 *
	 * @param Closure(TValue): mixed $selector
	 */
	public function sortedByDescending( Closure $selector ): static {
		return $this->pipe( 'sortedByDescending', $selector );
	}

	/**
	 * Reverse element order.
	 */
	public function reversed(): static {
		return $this->pipe( 'reversed', null );
	}

	/**
	 * Side-effect per element, lazy — fires during materialization.
	 *
	 * @param Closure(TValue): void $action
	 */
	public function onEach( Closure $action ): static {
		return $this->pipe( 'onEach', $action );
	}

	/**
	 * Split into chunks of $size.
	 *
	 * @return Klist<array<TValue>>
	 */
	public function chunked( int $size ): static {
		return $this->pipe( 'chunked', $size );
	}

	// ===============================================================
	// Terminal operations — force evaluation
	// ===============================================================

	/**
	 * @return array<int, TValue>
	 */
	#[Override]
	public function toArray(): array {
		return $this->materialize();
	}

	#[Override]
	public function count(): int {
		return count( $this->materialize() );
	}

	#[Override]
	public function isEmpty(): bool {
		// Fast path: no pipeline
		if ( empty( $this->pipeline ) ) {
			return empty( $this->materialized ?? $this->source );
		}
		// Already materialized
		if ( $this->materialized !== null ) {
			return empty( $this->materialized );
		}
		// Short-circuit for fuseable pipelines
		if ( $this->isFullyFuseable() ) {
			$filtered = self::filtered();
			foreach ( $this->source as $item ) {
				if ( $this->applyFuseableChain( $item ) !== $filtered ) {
					return false;
				}
			}

			return true;
		}

		return empty( $this->materialize() );
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
		if ( $this->materialized !== null ) {
			return $this->materialized[0] ?? null;
		}
		if ( empty( $this->pipeline ) ) {
			return $this->source[0] ?? null;
		}
		// Short-circuit for fuseable pipelines
		if ( $this->isFullyFuseable() ) {
			$filtered = self::filtered();
			foreach ( $this->source as $item ) {
				$result = $this->applyFuseableChain( $item );
				if ( $result !== $filtered ) {
					return $result;
				}
			}

			return null;
		}

		return $this->materialize()[0] ?? null;
	}

	/**
	 * @return TValue
	 * @throws Exception
	 */
	public function first(): mixed {
		$data = $this->materialize();
		if ( empty( $data ) ) {
			throw new Exception( "List is empty" );
		}

		return $data[0];
	}

	/**
	 * @return TValue|null
	 */
	public function lastOrNull(): mixed {
		$data = $this->materialize();

		return empty( $data ) ? null : $data[ count( $data ) - 1 ];
	}

	/**
	 * @return TValue
	 * @throws Exception
	 */
	public function last(): mixed {
		$data = $this->materialize();
		if ( empty( $data ) ) {
			throw new Exception( "List is empty" );
		}

		return $data[ count( $data ) - 1 ];
	}

	/**
	 * @return TValue
	 * @throws Exception if the list does not contain exactly one element.
	 */
	public function single(): mixed {
		$data = $this->materialize();
		if ( count( $data ) !== 1 ) {
			throw new Exception( "List does not contain exactly one element" );
		}

		return $data[0];
	}

	/**
	 * @return TValue|null null if the list does not contain exactly one element.
	 */
	public function singleOrNull(): mixed {
		$data = $this->materialize();

		return count( $data ) === 1 ? $data[0] : null;
	}

	/**
	 * True if any element matches.
	 *
	 * @param Closure(TValue): bool $predicate
	 */
	#[Override]
	public function any( Closure $predicate ): bool {
		// Short-circuit for fuseable pipelines
		if ( $this->materialized === null && $this->isFullyFuseable() ) {
			$filtered = self::filtered();
			foreach ( $this->source as $item ) {
				$result = $this->applyFuseableChain( $item );
				if ( $result !== $filtered && $predicate( $result ) ) {
					return true;
				}
			}

			return false;
		}

		foreach ( $this->materialize() as $item ) {
			if ( $predicate( $item ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * True if all elements match.
	 *
	 * @param Closure(TValue): bool $predicate
	 */
	#[Override]
	public function all( Closure $predicate ): bool {
		foreach ( $this->materialize() as $item ) {
			if ( ! $predicate( $item ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * True if no element matches.
	 *
	 * @param Closure(TValue): bool $predicate
	 */
	public function none( Closure $predicate ): bool {
		return ! $this->any( $predicate );
	}

	/**
	 * @param Closure(mixed, TValue): mixed $transform
	 * @param mixed|null $initial
	 *
	 * @return mixed
	 */
	#[Override]
	public function reduce( Closure $transform, mixed $initial = null ): mixed {
		$carry = $initial;
		foreach ( $this->materialize() as $item ) {
			$carry = $transform( $carry, $item );
		}

		return $carry;
	}

	/**
	 * Kotlin-style fold (initial value first).
	 *
	 * @param mixed $initial
	 * @param Closure(mixed, TValue): mixed $operation
	 *
	 * @return mixed
	 */
	public function fold( mixed $initial, Closure $operation ): mixed {
		return $this->reduce( $operation, $initial );
	}

	/**
	 * @param Closure(TValue): int|float $selector
	 */
	public function sumOf( Closure $selector ): int|float {
		$sum = 0;
		foreach ( $this->materialize() as $item ) {
			$sum += $selector( $item );
		}

		return $sum;
	}

	/**
	 * @param Closure(TValue): mixed $selector
	 *
	 * @return TValue|null
	 */
	public function minByOrNull( Closure $selector ): mixed {
		$minItem = null;
		$minSel  = null;
		foreach ( $this->materialize() as $item ) {
			$sel = $selector( $item );
			if ( $minSel === null || $sel < $minSel ) {
				$minSel  = $sel;
				$minItem = $item;
			}
		}

		return $minItem;
	}

	/**
	 * @param Closure(TValue): mixed $selector
	 *
	 * @return TValue|null
	 */
	public function maxByOrNull( Closure $selector ): mixed {
		$maxItem = null;
		$maxSel  = null;
		foreach ( $this->materialize() as $item ) {
			$sel = $selector( $item );
			if ( $maxSel === null || $sel > $maxSel ) {
				$maxSel  = $sel;
				$maxItem = $item;
			}
		}

		return $maxItem;
	}

	/**
	 * @return int -1 if not found.
	 */
	public function indexOf( mixed $element ): int {
		$index = array_search( $element, $this->materialize(), true );

		return $index === false ? - 1 : $index;
	}

	/**
	 * @param Closure(TValue): bool $predicate
	 *
	 * @return int -1 if not found.
	 */
	public function indexOfFirst( Closure $predicate ): int {
		foreach ( $this->materialize() as $index => $item ) {
			if ( $predicate( $item ) ) {
				return $index;
			}
		}

		return - 1;
	}

	public function contains( mixed $element ): bool {
		return in_array( $element, $this->materialize(), true );
	}

	#[Override]
	public function join( string $separator = '' ): string {
		return implode( $separator, $this->materialize() );
	}

	/**
	 * Execute a side-effect for each element (terminal).
	 *
	 * @param Closure(TValue): void $transform
	 *
	 * @return static
	 */
	#[Override]
	public function foreach( Closure $transform ): static {
		foreach ( $this->materialize() as $item ) {
			$transform( $item );
		}

		return $this;
	}

	/**
	 * Split elements into [matching, non-matching] pair.
	 *
	 * @param Closure(TValue): bool $predicate
	 *
	 * @return array{Klist<TValue>, Klist<TValue>}
	 */
	public function partition( Closure $predicate ): array {
		$matching    = [];
		$nonMatching = [];
		foreach ( $this->materialize() as $item ) {
			if ( $predicate( $item ) ) {
				$matching[] = $item;
			} else {
				$nonMatching[] = $item;
			}
		}

		return [ new static( $matching ), new static( $nonMatching ) ];
	}

	/**
	 * Group elements by a key selector.
	 *
	 * @param Closure(TValue): array-key $keySelector
	 *
	 * @return Kmap<array-key, Klist<TValue>>
	 */
	public function groupBy( Closure $keySelector ): Kmap {
		$groups = [];
		foreach ( $this->materialize() as $item ) {
			$key             = $keySelector( $item );
			$groups[ $key ][] = $item;
		}

		return new Kmap( array_map( fn( $group ) => new static( $group ), $groups ) );
	}

	/**
	 * Transform each element to a key-value pair and collect as a Kmap.
	 * The closure should return a single-entry associative array: [$key => $value].
	 *
	 * @template TMapKey of array-key
	 * @template TMapValue
	 * @param Closure(TValue): array<TMapKey, TMapValue> $transform
	 *
	 * @return Kmap<TMapKey, TMapValue>
	 */
	public function associate( Closure $transform ): Kmap {
		$result = [];
		foreach ( $this->materialize() as $item ) {
			$entry                 = $transform( $item );
			$result[ key( $entry ) ] = current( $entry );
		}

		return new Kmap( $result );
	}

	/**
	 * Key each element by a selector, value is the element itself.
	 *
	 * @param Closure(TValue): array-key $keySelector
	 *
	 * @return Kmap<array-key, TValue>
	 */
	public function associateBy( Closure $keySelector ): Kmap {
		$result = [];
		foreach ( $this->materialize() as $item ) {
			$result[ $keySelector( $item ) ] = $item;
		}

		return new Kmap( $result );
	}

	/**
	 * Backward-compatible alias for associate().
	 *
	 * @template TMap
	 * @param Closure(TValue): TMap $transform
	 *
	 * @return Kmap
	 */
	public function mapOf( Closure $transform ): Kmap {
		return $this->associate( $transform );
	}

	/**
	 * Pair elements from this list with another iterable.
	 *
	 * @template TOther
	 * @param iterable<TOther> $other
	 *
	 * @return Klist<array{TValue, TOther}>
	 */
	public function zip( iterable $other ): static {
		$data       = $this->materialize();
		$otherArray = $other instanceof CollectionInterface
			? $other->toArray()
			: ( is_array( $other ) ? $other : iterator_to_array( $other ) );
		$result     = [];
		$count      = min( count( $data ), count( $otherArray ) );
		for ( $i = 0; $i < $count; $i++ ) {
			$result[] = [ $data[ $i ], $otherArray[ $i ] ];
		}

		return new static( $result );
	}

	// ===============================================================
	// Merge / conversion
	// ===============================================================

	/**
	 * @param array<int, TValue> $array
	 *
	 * @return static
	 */
	#[Override]
	public function mergeArray( array $array ): static {
		$data = array_merge( $this->materialize(), $array );
		if ( $this->mutable ) {
			$this->materialized = $data;

			return $this;
		}

		return new static( $data );
	}

	/**
	 * @param CollectionInterface<int, TValue> $collection
	 *
	 * @return static
	 */
	#[Override]
	public function merge( CollectionInterface $collection ): static {
		return $this->mergeArray( $collection->toArray() );
	}

	#[Override]
	public function toMutable(): static {
		return $this->mutable ? $this : new static( $this->materialize(), true );
	}

	#[Override]
	public function toImmutable(): static {
		return ! $this->mutable ? $this : new static( $this->materialize(), false );
	}

	/**
	 * @param Closure(Klist<TValue>): void $predicate
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

	// ===============================================================
	// Mutation (mutable only)
	// ===============================================================

	/**
	 * Append a value.
	 *
	 * @param TValue $value
	 *
	 * @throws Exception
	 */
	public function add( mixed $value ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Cannot modify immutable list" );
		}
		$this->materialized[] = $value;
	}

	// ===============================================================
	// Iterator
	// ===============================================================

	#[Override]
	public function current(): mixed {
		$data = $this->materialize();

		return $data[ $this->iteratorIndex ] ?? null;
	}

	#[Override]
	public function next(): void {
		$this->iteratorIndex++;
	}

	/**
	 * Returns the current item and advances the pointer.
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
	public function key(): mixed {
		return $this->valid() ? $this->iteratorIndex : null;
	}

	#[Override]
	public function valid(): bool {
		return $this->iteratorIndex < count( $this->materialize() );
	}

	#[Override]
	public function rewind(): void {
		$this->iteratorIndex = 0;
	}

	// ===============================================================
	// ArrayAccess
	// ===============================================================

	#[Override]
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->materialize()[ $offset ] );
	}

	#[Override]
	public function offsetGet( mixed $offset ): mixed {
		return $this->materialize()[ $offset ];
	}

	/**
	 * @throws Exception
	 */
	#[Override]
	public function offsetSet( mixed $offset, mixed $value ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Cannot modify immutable list" );
		}
		if ( $offset === null ) {
			$this->materialized[] = $value;
		} else {
			$this->materialized[ $offset ] = $value;
		}
	}

	/**
	 * @throws Exception
	 */
	#[Override]
	public function offsetUnset( mixed $offset ): void {
		if ( ! $this->mutable ) {
			throw new Exception( "Cannot modify immutable list" );
		}
		unset( $this->materialized[ $offset ] );
		$this->materialized = array_values( $this->materialized );
	}

	// ===============================================================
	// Countable & JsonSerializable (via terminal)
	// ===============================================================

	#[Override]
	public function jsonSerialize(): mixed {
		return $this->materialize();
	}

	// ===============================================================
	// Pipeline internals
	// ===============================================================

	/**
	 * Append a step to the pipeline (immutable) or apply immediately (mutable).
	 */
	private function pipe( string $type, mixed $arg ): static {
		if ( $this->mutable ) {
			if ( in_array( $type, self::FUSEABLE, true ) ) {
				$this->materialized = $this->applyFusedGroup( $this->materialized, [ [ $type, $arg ] ] );
			} else {
				$this->materialized = $this->applyBulkStep( $this->materialized, [ $type, $arg ] );
			}

			return $this;
		}

		return new static(
			source: $this->source,
			mutable: false,
			pipeline: [ ...$this->pipeline, [ $type, $arg ] ],
		);
	}

	/**
	 * Evaluate the full pipeline and cache the result.
	 *
	 * @return array<int, TValue>
	 */
	private function materialize(): array {
		if ( $this->materialized !== null ) {
			return $this->materialized;
		}
		if ( empty( $this->pipeline ) ) {
			$this->materialized = array_values( $this->source );

			return $this->materialized;
		}

		$data          = $this->source;
		$fuseableGroup = [];

		foreach ( $this->pipeline as $step ) {
			[ $type ] = $step;

			if ( in_array( $type, self::FUSEABLE, true ) ) {
				$fuseableGroup[] = $step;
				continue;
			}

			// Flush the accumulated fuseable group as a single pass
			if ( ! empty( $fuseableGroup ) ) {
				$data          = $this->applyFusedGroup( $data, $fuseableGroup );
				$fuseableGroup = [];
			}

			$data = $this->applyBulkStep( $data, $step );
		}

		if ( ! empty( $fuseableGroup ) ) {
			$data = $this->applyFusedGroup( $data, $fuseableGroup );
		}

		$this->materialized = array_values( $data );

		return $this->materialized;
	}

	/**
	 * Apply multiple fuseable steps in a single pass over the data.
	 */
	private function applyFusedGroup( array $data, array $steps ): array {
		$result   = [];
		$filtered = self::filtered();

		foreach ( $data as $item ) {
			$value = $item;
			$skip  = false;

			foreach ( $steps as [ $type, $arg ] ) {
				switch ( $type ) {
					case 'filter':
						if ( ! $arg( $value ) ) {
							$skip = true;
							break 2;
						}
						break;
					case 'map':
						$value = $arg( $value );
						break;
					case 'mapNotNull':
						$value = $arg( $value );
						if ( $value === null ) {
							$skip = true;
							break 2;
						}
						break;
					case 'onEach':
						$arg( $value );
						break;
				}
			}

			if ( ! $skip ) {
				$result[] = $value;
			}
		}

		return $result;
	}

	/**
	 * Apply a single non-fuseable (cardinality-changing) step.
	 */
	private function applyBulkStep( array $data, array $step ): array {
		[ $type, $arg ] = $step;

		return match ( $type ) {
			'flatMap'             => $this->bulkFlatMap( $data, $arg ),
			'flatten'             => $this->bulkFlatten( $data ),
			'take'                => array_slice( $data, 0, $arg ),
			'drop'                => array_slice( $data, $arg ),
			'distinct'            => array_values( array_unique( $data, SORT_REGULAR ) ),
			'distinctBy'          => $this->bulkDistinctBy( $data, $arg ),
			'sortedBy'            => $this->bulkSort( $data, $arg, false ),
			'sortedByDescending'  => $this->bulkSort( $data, $arg, true ),
			'reversed'            => array_reverse( $data ),
			'chunked'             => array_chunk( $data, $arg ),
			default               => $data,
		};
	}

	private function bulkFlatMap( array $data, Closure $transform ): array {
		$result = [];
		foreach ( $data as $item ) {
			$mapped = $transform( $item );
			if ( $mapped instanceof CollectionInterface ) {
				array_push( $result, ...$mapped->toArray() );
			} elseif ( is_array( $mapped ) ) {
				array_push( $result, ...$mapped );
			} else {
				$result[] = $mapped;
			}
		}

		return $result;
	}

	private function bulkFlatten( array $data ): array {
		$result = [];
		foreach ( $data as $item ) {
			if ( $item instanceof CollectionInterface ) {
				array_push( $result, ...$item->toArray() );
			} elseif ( is_array( $item ) ) {
				array_push( $result, ...$item );
			} else {
				$result[] = $item;
			}
		}

		return $result;
	}

	private function bulkDistinctBy( array $data, Closure $selector ): array {
		$seen   = [];
		$result = [];
		foreach ( $data as $item ) {
			$key = serialize( $selector( $item ) );
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ] = true;
				$result[]     = $item;
			}
		}

		return $result;
	}

	private function bulkSort( array $data, Closure $selector, bool $descending ): array {
		usort( $data, function ( $a, $b ) use ( $selector, $descending ) {
			$cmp = $selector( $a ) <=> $selector( $b );

			return $descending ? - $cmp : $cmp;
		} );

		return $data;
	}

	/**
	 * Apply the fuseable pipeline to a single element.
	 * Returns the filtered() sentinel if the element should be excluded.
	 */
	private function applyFuseableChain( mixed $item ): mixed {
		$value    = $item;
		$filtered = self::filtered();

		foreach ( $this->pipeline as [ $type, $arg ] ) {
			switch ( $type ) {
				case 'filter':
					if ( ! $arg( $value ) ) {
						return $filtered;
					}
					break;
				case 'map':
					$value = $arg( $value );
					break;
				case 'mapNotNull':
					$value = $arg( $value );
					if ( $value === null ) {
						return $filtered;
					}
					break;
				case 'onEach':
					$arg( $value );
					break;
			}
		}

		return $value;
	}

	/**
	 * True if every pipeline step is fuseable (can short-circuit).
	 */
	private function isFullyFuseable(): bool {
		foreach ( $this->pipeline as [ $type ] ) {
			if ( ! in_array( $type, self::FUSEABLE, true ) ) {
				return false;
			}
		}

		return true;
	}
}

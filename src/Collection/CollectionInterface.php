<?php

namespace Axpecto\Collection;

use ArrayAccess;
use Closure;
use Countable;
use Iterator;
use JsonSerializable;

/**
 * Interface CollectionInterface
 *
 * Represents a collection that supports various functional operations, including filtering,
 * mapping, merging, and transformation between mutable and immutable states.
 *
 * @template TKey
 * @template TValue
 * @extends ArrayAccess<TKey, TValue>
 * @extends Iterator<TKey, TValue>
 */
interface CollectionInterface extends ArrayAccess, Countable, Iterator, JsonSerializable {

	/**
	 * Convert the collection to an array.
	 *
	 * @return array<TKey, TValue>
	 */
	public function toArray(): array;

	/**
	 * Check if the collection is empty.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool;

	/**
	 * Check if the collection is not empty.
	 *
	 * @return bool
	 */
	public function isNotEmpty(): bool;

	/**
	 * Filter the collection based on a predicate.
	 *
	 * @param Closure(TValue):bool $predicate
	 *
	 * @return static
	 */
	public function filter( Closure $predicate ): static;

	/**
	 * Filter out null values from the collection.
	 *
	 * @return static
	 */
	public function filterNotNull(): static;

	/**
	 * Apply a transformation to each element in the collection.
	 *
	 * @template TOut
	 * @param Closure(TValue):TOut $transform
	 *
	 * @return CollectionInterface<TKey, TOut>
	 */
	public function map( Closure $transform ): CollectionInterface;

	/**
	 * Execute a function for each element in the collection.
	 *
	 * @param Closure(TValue):void $transform
	 *
	 * @return static
	 */
	public function foreach( Closure $transform ): static;

	/**
	 * Determine if any element in the collection satisfies a predicate.
	 *
	 * @param Closure(TValue):bool $predicate
	 *
	 * @return bool
	 */
	public function any( Closure $predicate ): bool;

	/**
	 * Retrieve the first element in the collection or null if empty.
	 *
	 * @return TValue|null
	 */
	public function firstOrNull(): mixed;

	/**
	 * Determine if all elements in the collection satisfy a predicate.
	 *
	 * @param Closure(TValue):bool $predicate
	 *
	 * @return bool
	 */
	public function all( Closure $predicate ): bool;

	/**
	 * Merge the collection with another array.
	 *
	 * @param array<TKey, TValue> $array
	 *
	 * @return static
	 */
	public function mergeArray( array $array ): static;

	/**
	 * Merge the collection with another collection.
	 *
	 * @param CollectionInterface<TKey, TValue> $collection
	 *
	 * @return static
	 */
	public function merge( CollectionInterface $collection ): static;

	/**
	 * Join elements of the collection into a string with a separator.
	 *
	 * @param string $separator
	 *
	 * @return string
	 */
	public function join( string $separator ): string;

	/**
	 * Apply a predicate if the collection is not empty.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param Closure(CollectionInterface<TKey, TValue>):void $predicate
	 *
	 * @return static
	 */
	public function maybe( Closure $predicate ): static;

	/**
	 * Convert the collection to a mutable variant.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @return static
	 */
	public function toMutable(): static;

	/**
	 * Convert the collection to an immutable variant.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @return static
	 */
	public function toImmutable(): static;

	/**
	 * Flatten nested elements within the collection.
	 *
	 * @return static
	 */
	public function flatten(): static;

	/**
	 * Reduce the collection to a single value using a callback.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param Closure(mixed, TValue):mixed $transform
	 * @param mixed|null $initial
	 *
	 * @return mixed
	 */
	public function reduce( Closure $transform, mixed $initial = null ): mixed;

	/**
	 * Returns the current item and advances the internal pointer.
	 *
	 * @return TValue|null
	 */
	public function nextAndGet(): mixed;
}

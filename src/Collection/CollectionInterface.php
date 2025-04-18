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
 */
interface CollectionInterface extends ArrayAccess, Countable, Iterator, JsonSerializable {

	/**
	 * Convert the collection to an array.
	 *
	 * @return array The collection as a PHP array.
	 */
	public function toArray(): array;

	/**
	 * Check if the collection is empty.
	 *
	 * @return bool True if empty, otherwise false.
	 */
	public function isEmpty(): bool;

	/**
	 * Check if the collection is not empty.
	 *
	 * @return bool True if not empty, otherwise false.
	 */
	public function isNotEmpty(): bool;

	/**
	 * Filter the collection based on a predicate.
	 *
	 * @param Closure $predicate A callback function to determine if an element should be included.
	 *
	 * @return static A new filtered collection.
	 */
	public function filter( Closure $predicate ): static;

	/**
	 * Filter out null values from the collection.
	 *
	 * @return static A new collection without null values.
	 */
	public function filterNotNull(): static;

	/**
	 * Apply a transformation to each element in the collection.
	 *
	 * @param Closure $transform The transformation function.
	 *
	 * @return static A new collection with transformed elements.
	 */
	public function map( Closure $transform ): static;

	/**
	 * Execute a function for each element in the collection.
	 *
	 * @param Closure $transform The function to execute.
	 *
	 * @return static The current collection for chaining.
	 */
	public function foreach( Closure $transform ): static;

	/**
	 * Determine if any element in the collection satisfies a predicate.
	 *
	 * @param Closure $predicate The predicate function.
	 *
	 * @return bool True if any element satisfies the predicate, otherwise false.
	 */
	public function any( Closure $predicate ): bool;

	/**
	 * Retrieve the first element in the collection or null if empty.
	 *
	 * @return mixed The first element or null.
	 */
	public function firstOrNull(): mixed;

	/**
	 * Determine if all elements in the collection satisfy a predicate.
	 *
	 * @param Closure $predicate The predicate function.
	 *
	 * @return bool True if all elements satisfy the predicate, otherwise false.
	 */
	public function all( Closure $predicate ): bool;

	/**
	 * Merge the collection with another array.
	 *
	 * @param array $array The array to merge.
	 *
	 * @return static A new collection with merged values.
	 */
	public function mergeArray( array $array ): static;

	/**
	 * Merge the collection with another collection.
	 *
	 * @param CollectionInterface $collection The collection to merge.
	 *
	 * @return static A new collection with merged values.
	 */
	public function merge( CollectionInterface $collection ): static;

	/**
	 * Join elements of the collection into a string with a separator.
	 *
	 * @param string $separator The separator to use between elements.
	 *
	 * @return string The joined string.
	 */
	public function join( string $separator ): string;

	/**
	 * Apply a predicate if the collection is not empty.
	 *
	 * @param Closure $predicate The predicate function.
	 *
	 * @return static The current collection for chaining.
	 */
	public function maybe( Closure $predicate ): static;

	/**
	 * Convert the collection to a mutable variant.
	 *
	 * @return static A mutable version of the collection.
	 */
	public function toMutable(): static;

	/**
	 * Convert the collection to an immutable variant.
	 *
	 * @return static An immutable version of the collection.
	 */
	public function toImmutable(): static;

	/**
	 * Flatten nested elements within the collection.
	 *
	 * @return static A new collection with flattened elements.
	 */
	public function flatten(): static;
}

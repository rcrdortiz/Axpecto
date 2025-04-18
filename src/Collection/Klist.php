<?php

namespace Axpecto\Collection;

use Closure;

/**
 * @template TValue
 * @implements CollectionInterface<int, TValue>
 */
class Klist implements CollectionInterface {
	/**
	 * @var Kmap<int, TValue>
	 */
	private Kmap $internalMap;

	/**
	 * @param array<int, TValue> $array
	 * @param bool               $mutable
	 */
	public function __construct( array $array = [], private readonly bool $mutable = false ) {
		$this->internalMap = new Kmap( $array, $this->mutable );
	}

	/**
	 * @return array<int, TValue>
	 */
	public function toArray(): array {
		return $this->internalMap->toArray();
	}

	public function isEmpty(): bool {
		return $this->internalMap->isEmpty();
	}

	public function isNotEmpty(): bool {
		return $this->internalMap->isNotEmpty();
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 *
	 * @return static
	 */
	public function filter( Closure $predicate ): static {
		$map = $this->internalMap->filter( fn( $key, $value ) => $predicate( $value ) );
		$map->resetKeys();

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
	}

	/**
	 * @return static
	 */
	public function filterNotNull(): static {
		$map = $this->internalMap->filterNotNull();

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
	}

	/**
	 * @template TOut
	 * @param Closure(TValue):TOut $transform
	 *
	 * @return Klist<TOut>
	 */
	public function map( Closure $transform ): static {
		$map = $this->internalMap->map( fn( $key, $value ) => [ $key => $transform( $value ) ] );

		return $this->mutable ? $this : new Klist( $map->toArray(), $this->mutable );
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	public function any( Closure $predicate ): bool {
		return $this->internalMap->any( $predicate );
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	public function all( Closure $predicate ): bool {
		return $this->internalMap->all( $predicate );
	}

	/**
	 * @param array<int, TValue> $array
	 *
	 * @return static
	 */
	public function mergeArray( array $array ): static {
		$map = $this->internalMap->mergeArray( $array );

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
	}

	/**
	 * @param CollectionInterface<int, TValue> $collection
	 *
	 * @return static
	 */
	public function merge( CollectionInterface $collection ): static {
		return $this->mergeArray( $collection->toArray() );
	}

	public function join( string $separator ): string {
		return $this->internalMap->join( $separator );
	}

	/**
	 * Executes the closure only if the list is not empty.
	 *
	 * @param Closure(Klist<TValue>):void $predicate
	 *
	 * @return static
	 */
	public function maybe( Closure $predicate ): static {
		count( $this ) > 0 && $predicate( $this );

		return $this;
	}

	/**
	 * @return static
	 */
	public function toMutable(): static {
		return $this->mutable ? $this : new static( $this->internalMap->toArray(), true );
	}

	/**
	 * @return static
	 */
	public function toImmutable(): static {
		return ! $this->mutable ? $this : new static( $this->internalMap->toArray(), false );
	}

	/**
	 * @return static
	 */
	public function flatten(): static {
		$map = $this->internalMap->flatten();

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
	}

	public function current(): mixed {
		return $this->internalMap->current();
	}

	public function next(): void {
		$this->internalMap->next();
	}

	public function key(): mixed {
		return $this->internalMap->key();
	}

	public function valid(): bool {
		return $this->internalMap->valid();
	}

	public function rewind(): void {
		$this->internalMap->rewind();
	}

	public function offsetExists( mixed $offset ): bool {
		return $this->internalMap->offsetExists( $offset );
	}

	public function offsetGet( mixed $offset ): mixed {
		return $this->internalMap->offsetGet( $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->internalMap->offsetSet( $offset, $value );
	}

	public function offsetUnset( mixed $offset ): void {
		$this->internalMap->offsetUnset( $offset );
	}

	public function count(): int {
		return $this->internalMap->count();
	}

	public function jsonSerialize(): mixed {
		return $this->internalMap->jsonSerialize();
	}

	/**
	 * Add a value to the end of the list.
	 *
	 * @param TValue $value
	 */
	public function add( mixed $value ): void {
		$this->internalMap->add( $this->internalMap->count(), $value );
	}

	/**
	 * Iterate over each item.
	 *
	 * @param Closure(TValue):void $transform
	 *
	 * @return static
	 */
	public function foreach( Closure $transform ): static {
		$this->internalMap->foreach( fn( $key, $value ) => $transform( $value ) );

		return $this;
	}

	/**
	 * @return TValue|null
	 */
	public function firstOrNull(): mixed {
		return $this->internalMap->firstOrNull();
	}

	/**
	 * @template TMap
	 * @param Closure(TValue):TMap $transform
	 *
	 * @return KMap<int, TMap>
	 */
	public function mapOf( Closure $transform ): KMap {
		return $this->internalMap->map( fn( $key, $value ) => $transform( $value ) );
	}
}

<?php

namespace Axpecto\Collection;

use Closure;
use Override;

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
	 * @param bool $mutable
	 */
	public function __construct( array $array = [], private readonly bool $mutable = false ) {
		$this->internalMap = new Kmap( $array, $this->mutable );
	}

	/**
	 * @return array<int, TValue>
	 */
	#[Override]
	public function toArray(): array {
		return $this->internalMap->toArray();
	}

	#[Override]
	public function isEmpty(): bool {
		return $this->internalMap->isEmpty();
	}

	#[Override]
	public function isNotEmpty(): bool {
		return $this->internalMap->isNotEmpty();
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 *
	 * @return static
	 */
	#[Override]
	public function filter( Closure $predicate ): static {
		$map = $this->internalMap->filter( fn( $key, $value ) => $predicate( $value ) );
		$map->resetKeys();

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
	}

	/**
	 * @return static
	 */
	#[Override]
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
	#[Override]
	public function map( Closure $transform ): static {
		$map = $this->internalMap->map( fn( $key, $value ) => [ $key => $transform( $value ) ] );

		return $this->mutable ? $this : new Klist( $map->toArray(), $this->mutable );
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	#[Override]
	public function any( Closure $predicate ): bool {
		return $this->internalMap->any( $predicate );
	}

	/**
	 * @param Closure(TValue):bool $predicate
	 */
	#[Override]
	public function all( Closure $predicate ): bool {
		return $this->internalMap->all( $predicate );
	}

	/**
	 * @param array<int, TValue> $array
	 *
	 * @return static
	 */
	#[Override]
	public function mergeArray( array $array ): static {
		$map = $this->internalMap->mergeArray( $array );

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
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
	public function join( string $separator = '' ): string {
		return $this->internalMap->join( $separator );
	}

	/**
	 * Executes the closure only if the list is not empty.
	 *
	 * @param Closure(Klist<TValue>):void $predicate
	 *
	 * @return static
	 */
	#[Override]
	public function maybe( Closure $predicate ): static {
		count( $this ) > 0 && $predicate( $this );

		return $this;
	}

	/**
	 * @return static
	 */
	#[Override]
	public function toMutable(): static {
		return $this->mutable ? $this : new static( $this->internalMap->toArray(), true );
	}

	/**
	 * @return static
	 */
	#[Override]
	public function toImmutable(): static {
		return ! $this->mutable ? $this : new static( $this->internalMap->toArray(), false );
	}

	/**
	 * @return static
	 */
	#[Override]
	public function flatten(): static {
		$map = $this->internalMap->flatten();

		return $this->mutable ? $this : new static( $map->toArray(), $this->mutable );
	}

	#[Override]
	public function current(): mixed {
		return $this->internalMap->current();
	}

	#[Override]
	public function next(): void {
		$this->internalMap->next();
	}

	/**
	 * Returns the current item and advances the internal pointer.
	 *
	 * @return TValue|null
	 */
	#[Override]
	public function nextAndGet(): mixed {
		return $this->internalMap->nextAndGet();
	}

	#[Override]
	public function key(): mixed {
		return $this->internalMap->key();
	}

	#[Override]
	public function valid(): bool {
		return $this->internalMap->valid();
	}

	#[Override]
	public function rewind(): void {
		$this->internalMap->rewind();
	}

	#[Override]
	public function offsetExists( mixed $offset ): bool {
		return $this->internalMap->offsetExists( $offset );
	}

	#[Override]
	public function offsetGet( mixed $offset ): mixed {
		return $this->internalMap->offsetGet( $offset );
	}

	#[Override]
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->internalMap->offsetSet( $offset, $value );
	}

	#[Override]
	public function offsetUnset( mixed $offset ): void {
		$this->internalMap->offsetUnset( $offset );
	}

	#[Override]
	public function count(): int {
		return $this->internalMap->count();
	}

	#[Override]
	public function jsonSerialize(): mixed {
		return $this->internalMap->jsonSerialize();
	}

	/**
	 * Add a value to the end of the list.
	 *
	 * @param TValue $value
	 *
	 * @throws \Exception
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
	#[Override]
	public function foreach( Closure $transform ): static {
		$this->internalMap->foreach( fn( $key, $value ) => $transform( $value ) );

		return $this;
	}

	/**
	 * @return TValue|null
	 */
	#[Override]
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
		return $this->internalMap->reduce( $transform, $initial );
	}
}

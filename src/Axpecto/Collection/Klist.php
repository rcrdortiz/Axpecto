<?php

namespace Axpecto\Collection;

use Closure;

class Klist implements CollectionInterface {
	private Kmap $internalMap;

	public function __construct( array $array = [], private readonly bool $mutable = false ) {
		$this->internalMap = new Kmap( $array, $this->mutable );
	}

	public function toArray(): array {
		return $this->internalMap->toArray();
	}

	public function isEmpty(): bool {
		return $this->internalMap->isEmpty();
	}

	public function isNotEmpty(): bool {
		return $this->internalMap->isNotEmpty();
	}

	public function filter( Closure $predicate ): static {
		$map = $this->internalMap->filter( fn( $key, $value ) => $predicate( $value ) );

		if ( ! $this->mutable ) {
			return new static( $map->toArray(), $this->mutable );
		}

		return $this;
	}

	public function filterNotNull(): static {
		$map = $this->internalMap->filterNotNull();

		if ( ! $this->mutable ) {
			return new static( $map->toArray(), $this->mutable );
		}

		return $this;
	}

	public function map( Closure $transform ): static {
		$map = $this->internalMap->map( fn( $key, $value ) => [ $key => $transform( $value ) ] );

		if ( ! $this->mutable ) {
			return new static( $map->toArray(), $this->mutable );
		}

		return $this;
	}

	public function any( Closure $predicate ): bool {
		return $this->internalMap->any( $predicate );
	}

	public function all( Closure $predicate ): bool {
		return $this->internalMap->all( $predicate );
	}

	public function mergeArray( array $array ): static {
		$map = $this->internalMap->mergeArray( $array );

		if ( ! $this->mutable ) {
			return new static( $map->toArray(), $this->mutable );
		}

		return $this;
	}

	public function merge( CollectionInterface $map ): static {
		return $this->mergeArray( $map->toArray() );
	}

	public function join( string $separator ): string {
		return $this->internalMap->join( $separator );
	}

	public function maybe( Closure $predicate ): static {
		$this->internalMap->maybe( $predicate );

		return $this;
	}

	public function toMutable(): static {
		if ( $this->mutable ) {
			return $this;
		}

		return new static( $this->internalMap->toArray(), true );
	}

	public function toImmutable(): static {
		if ( ! $this->mutable ) {
			return $this;
		}

		return new static( $this->internalMap->toArray(), false );
	}

	public function flatten(): static {
		$map = $this->internalMap->flatten();
		if ( ! $this->mutable ) {
			return new static( $map->toArray(), $this->mutable );
		}

		return $this;
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

	public function add( mixed $value ): void {
		$this->internalMap->add( $this->internalMap->count(), $value );
	}

	public function foreach( Closure $transform ): static {
		$this->internalMap->foreach( fn( $key, $value ) => $transform( $value ) );

		return $this;
	}

	public function firstOrNull(): mixed {
		return $this->internalMap->firstOrNull();
	}

	public function mapOf( Closure $transform ): KMap {
		return $this->internalMap->map( fn( $key, $value ) => $transform( $value ) );
	}
}

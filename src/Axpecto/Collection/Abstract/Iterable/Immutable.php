<?php

namespace Axpecto\Collection\Abstract\Iterable;

use ArrayAccess;
use Closure;
use Countable;
use Axpecto\Collection\Concrete\Kmap;
use Exception;
use Iterator;
use JsonSerializable;

abstract class Immutable implements ArrayAccess, Countable, Iterator, JsonSerializable {

	public readonly int $size;

	public function __construct(
		protected array $array = [],
		protected int $index = 0,
	) {
		$this->size = $this->count();
	}

	public function nextElement(): mixed {
		$current = null;
		if ( $this->valid() ) {
			$current = $this->current();
			$this->next();
		}

		return $current;
	}

	public function offsetSet( $offset, $value ): void {
		// Immutable
	}

	public function offsetUnset( $offset ): void {
		// Immutable
	}

	public function current(): mixed {
		return $this->array[ $this->index ];
	}

	public function next(): void {
		$this->index ++;
	}

	public function key(): int {
		return key( $this->array );
	}

	public function valid(): bool {
		return isset( $this->array[ $this->index ] );
	}

	public function rewind(): void {
		$this->index = 0;
	}

	public function offsetExists( $offset ): bool {
		return isset( $this->array[ $offset ] );
	}

	public function ifNotEmpty( Closure $block): static {
		if ( $this->array ) {
			$block( $this->array );
		}

		return $this;
	}

	public function ifEmpty( Closure $block): static {
		if ( ! $this->array ) {
			$block( $this->array );
		}

		return $this;
	}

	public function offsetGet( $offset ): mixed {
		return $this->array[ $offset ];
	}

	public function count(): int {
		return count( $this->array );
	}

	public function isEmpty(): bool {
		return count( $this ) === 0;
	}

	public function isNotEmpty(): bool {
		return count( $this ) > 0;
	}

	public function jsonSerialize(): string {
		return json_encode( $this->array );
	}

	public function toArray(): array {
		return $this->array;
	}

	public abstract function filter( Closure $predicate ): static;

	public abstract function filterNotNull(): static;

	public function reduce( Closure $reducer, mixed $initial = null ): mixed {
		return array_reduce( $this->toArray(), $reducer, $initial );
	}

	public function mapOf( Closure $transform ): Kmap {
		$map = [];
		foreach ( $this->toArray() as $item ) {
			$item = $transform( $item );
			if ( ! is_array( $item ) || count( $item ) !== 2 ) {
				throw new Exception( "Can't convert list to map, item is not a valid tuple." );
			}
			$key         = array_shift( $item );
			$value       = array_pop( $item );
			$map[ $key ] = $value;
		}

		return mapOf( $map );
	}
}

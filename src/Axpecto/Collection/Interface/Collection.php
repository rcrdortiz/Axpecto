<?php

namespace Axpecto\Collection\Interface;

use Closure;

interface Collection {
	public function map( Closure $transform );

	public function foreach( Closure $action );

	public function filter( Closure $predicate );

	public function any( Closure $predicate ): bool;

	public function flatten();

	public function filterNotNull();
}
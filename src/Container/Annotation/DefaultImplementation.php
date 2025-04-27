<?php

namespace Axpecto\Container\Annotation;

use Attribute;
use Axpecto\Annotation\Annotation;

#[Attribute]
class DefaultImplementation extends Annotation {

	/**
	 * @template T
	 *
	 * @param class-string<T> $className
	 */
	public function __construct(
		public readonly string $className,
	) {
	}
}
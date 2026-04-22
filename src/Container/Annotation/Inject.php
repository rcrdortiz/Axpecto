<?php

namespace Axpecto\Container\Annotation;

use Attribute;
use Axpecto\Annotation\Annotation;

/**
 * Class Inject
 *
 * This annotation is used for property injection in the container. It allows arguments to be passed when injecting
 * dependencies. This is intended to mark properties for automatic dependency injection.
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class Inject extends Annotation {
	/**
	 * @template T
	 * Constructor for the Inject annotation.
	 *
	 * @param class-string<T>|null $class
	 */
	public function __construct(
		public readonly ?string $class = null,
	) {
	}
}

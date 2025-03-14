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
	 * Constructor for the Inject annotation.
	 *
	 * @param array $args Arguments to be injected into the property.
	 */
	public function __construct(
		public readonly ?string $class = null,
		public readonly array $args = [],
	) {
	}
}

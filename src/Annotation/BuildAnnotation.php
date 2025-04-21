<?php

namespace Axpecto\Annotation;

use Attribute;
use Axpecto\ClassBuilder\BuildHandler;

#[Attribute]
class BuildAnnotation extends Annotation {
	/**
	 * The builder for the annotation, used during the build phase.
	 *
	 * @var BuildHandler|null
	 */
	protected ?BuildHandler $builder = null;

	/**
	 * Gets the BuildHandler for this annotation, if available.
	 *
	 * @return BuildHandler|null The builder for the annotation, or null if not set.
	 */
	public function getBuilder(): ?BuildHandler {
		return $this->builder;
	}
}
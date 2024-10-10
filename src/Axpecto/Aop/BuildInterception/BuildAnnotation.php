<?php

namespace Axpecto\Aop\BuildInterception;

use Attribute;
use Axpecto\Aop\Annotation;

#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )] abstract class BuildAnnotation {
	private BuildAnnotationHandler $builder;

	public function __construct(
		public readonly ?string $builderClass = null,
	) {
	}

	public function setBuilder( BuildAnnotationHandler $handler ): void {
		if ( $handler instanceof $this->builderClass ) {
			$this->builder = $handler;
		}
	}

	public function getBuilder(): ?BuildAnnotationHandler {
		return $this->builder;
	}
}

<?php

namespace Axpecto\Aop\BuildInterception;

use Attribute;
use Axpecto\Aop\Annotation;

#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )] abstract class BuildAnnotation extends Annotation {
	private BuildAnnotationHandler $builder;

	public function __construct(
		public readonly ?string $builderClass = null,
		?string $handlerClass = null,
	) {
		parent::__construct( $handlerClass );
	}

	public function setBuilder( BuildAnnotationHandler $handler ) {
		if ( $handler instanceof $this->builderClass ) {
			$this->builder = $handler;
		}
	}

	public function getBuilder(): ?BuildAnnotationHandler {
		return $this->builder;
	}
}

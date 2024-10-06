<?php

namespace Axpecto\Aop;

use Attribute;

#[Attribute] abstract class Annotation {

	protected ?AnnotationHandler $handler = null;

	public function __construct(
		public readonly ?string $handlerClass = null,
	) {
	}

	public function setHandler( AnnotationHandler $handler ) {
		if ( $handler instanceof $this->handlerClass ) {
			$this->handler = $handler;
		}
	}

	public function getHandler(): ?AnnotationHandler {
		return $this->handler;
	}
}
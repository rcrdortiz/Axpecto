<?php

namespace Axpecto\Aop\BuildInterception;

use Axpecto\Aop\Annotation;
use Axpecto\Collection\Concrete\Klist;

class BuildChain {

	/**
	 * @param Klist<Annotation> $annotations
	 */
	public function __construct(
		protected Klist $annotations,
		protected string $class,
	) {
	}

	public function proceed(): ?string {
		/** @var BuildAnnotation $annotation */
		$annotation = $this->annotations->nextElement();
		/** @var BuildAnnotationHandler $builder */
		$builder = $annotation?->getBuilder();

		return $builder?->intercept( $this, $annotation, $this->class );
	}
}
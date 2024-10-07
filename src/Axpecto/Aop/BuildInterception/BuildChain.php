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
		protected ?string $method,
		protected BuildOutput $output,
	) {
	}

	// We need to merge output instead of replacing it with the last output you dork.
	public function proceed( ?BuildOutput $newOutput = null ): BuildOutput {
		$this->output = $newOutput ?? $this->output;

		/** @var BuildAnnotation $annotation */
		$annotation = $this->annotations->nextElement();
		/** @var BuildAnnotationHandler $builder */
		$builder = $annotation?->getBuilder();

		if ( $builder ) {
			return $builder->intercept( $this, $annotation, $this->class, $this->method, $this->output );
		}

		return $this->output;
	}
}
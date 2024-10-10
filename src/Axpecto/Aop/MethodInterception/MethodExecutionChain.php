<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Aop\Annotation;
use Axpecto\Collection\Concrete\Klist;

/**
 * Class MethodExecutionChain
 *
 * Manages the execution of method annotations in a chain. It processes each annotation
 * and allows for intercepting and modifying method behavior through annotations.
 */
class MethodExecutionChain {
	/**
	 * @param Method            $method      The method to be executed.
	 * @param Klist<Annotation> $annotations The list of annotations to process.
	 */
	public function __construct(
		protected Method $method,
		protected Klist $annotations,
	) {
	}

	/**
	 * Returns the current method being processed.
	 *
	 * @return Method
	 */
	public function getMethod(): Method {
		return $this->method;
	}

	/**
	 * Proceeds with the next annotation in the chain or calls the method if no more annotations.
	 *
	 * @param Method|null $nextMethod An optional method to use for further processing.
	 *
	 * @return mixed The result of the method call or the annotation's intercept logic.
	 */
	public function proceed( ?Method $nextMethod = null ): mixed {
		// Update the method if a new one is provided.
		$this->method = $nextMethod ?? $this->method;

		// Retrieve the next annotation in the list.
		$annotation = $this->annotations->nextElement();

		// If the annotation is not of type MethodExecutionAnnotation, proceed with the method call.
		if ( ! $annotation instanceof MethodExecutionAnnotation ) {
			return $this->method->call();
		}

		// Call the intercept method on the annotation's handler, allowing it to modify behavior.
		return $annotation->getHandler()->intercept( $this, $annotation );
	}
}

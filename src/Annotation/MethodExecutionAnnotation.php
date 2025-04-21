<?php

namespace Axpecto\Annotation;

use Attribute;
use Axpecto\MethodExecution\MethodExecutionHandler;

#[Attribute]
class MethodExecutionAnnotation extends BuildAnnotation {
	/**
	 * The handler for processing the method execution annotation.
	 *
	 * @var MethodExecutionHandler|null
	 */
	protected ?MethodExecutionHandler $methodExecutionHandler = null;

	/**
	 * Gets the MethodExecutionHandler for this annotation, if available.
	 *
	 * @return MethodExecutionHandler|null The handler for method execution, or null if not set.
	 */
	public function getMethodExecutionHandler(): ?MethodExecutionHandler {
		return $this->methodExecutionHandler;
	}
}
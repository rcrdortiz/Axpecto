<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Aop\AnnotationHandler;

/**
 * Interface MethodExecutionAnnotationHandler
 *
 * This interface defines the contract for handling method execution annotations.
 * It extends the generic `AnnotationHandler` and provides a method for intercepting
 * the method execution chain.
 */
interface MethodExecutionAnnotationHandler extends AnnotationHandler {
	/**
	 * Intercepts a method execution within the provided chain.
	 *
	 * @param MethodExecutionChain      $chain      The chain representing the current method execution flow.
	 * @param MethodExecutionAnnotation $annotation The annotation associated with the method execution.
	 *
	 * @return mixed The result of the intercepted method execution, which could be modified or augmented by the handler.
	 */
	public function intercept(
		MethodExecutionChain $chain,
		MethodExecutionAnnotation $annotation
	): mixed;
}

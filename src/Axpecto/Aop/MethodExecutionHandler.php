<?php

namespace Axpecto\Aop;

use Axpecto\Aop\MethodExecution\MethodExecutionChain;
use Axpecto\Aop\MethodExecution\MethodExecutionContext;
use Exception;

/**
 * Class MethodExecutionHandler
 *
 * Provides a base class for handling method execution annotations in Aspect-Oriented Programming (AOP).
 * This class defines methods for intercepting, rolling back, and committing changes during method execution.
 *
 * Subclasses can override the provided methods to modify or augment the behavior of method execution chains
 * using annotations. This class also ensures that the method execution process can be handled gracefully in the
 * event of errors.
 */
abstract class MethodExecutionHandler {

	/**
	 * Intercepts a method execution within the provided chain.
	 *
	 * This method is responsible for handling and possibly modifying the execution of a method
	 * based on the annotation and execution context. Subclasses should override this method to
	 * provide custom behavior during method execution.
	 *
	 * @param Annotation             $annotation             The annotation associated with the method execution.
	 * @param MethodExecutionContext $methodExecutionContext The context of the method being executed.
	 * @param MethodExecutionChain   $chain                  The chain representing the method execution flow.
	 *
	 * @return MethodExecutionContext Modified or original method execution context after interception.
	 */
	public function intercept(
		Annotation $annotation,
		MethodExecutionContext $methodExecutionContext,
		MethodExecutionChain $chain,
	): MethodExecutionContext {
		return $methodExecutionContext;
	}

	/**
	 * Handles rollback operations if an exception occurs during method execution.
	 *
	 * Subclasses may override this method to provide custom rollback logic in case of an error.
	 *
	 * @param Exception              $e          The exception thrown during method execution.
	 * @param Annotation             $annotation The annotation associated with the method execution.
	 * @param MethodExecutionContext $context    The context of the method being executed.
	 *
	 * @return void
	 */
	public function rollback( Exception $e, Annotation $annotation, MethodExecutionContext $context ): void {
		// Optional: override to provide custom rollback behavior
	}

	/**
	 * Handles commit operations after successful method execution.
	 *
	 * This method can be overridden to provide additional behavior after a method completes successfully.
	 *
	 * @param mixed                  $value      The return value of the method execution.
	 * @param Annotation             $annotation The annotation associated with the method execution.
	 * @param MethodExecutionContext $context    The context of the method being executed.
	 *
	 * @return void
	 */
	public function commit( mixed $value, Annotation $annotation, MethodExecutionContext $context ): void {
		// Optional: override to provide custom commit behavior
	}
}

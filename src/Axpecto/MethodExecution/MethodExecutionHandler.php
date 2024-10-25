<?php

namespace Axpecto\MethodExecution;

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
interface MethodExecutionHandler {

	/**
	 * Intercepts a method execution within the provided chain.
	 *
	 * This method is responsible for handling and possibly modifying the execution of a method
	 * based on the annotation and execution context. Subclasses should override this method to
	 * provide custom behavior during method execution.
	 *
	 * @param MethodExecutionContext $methodExecutionContext The context of the method being executed.
	 *
	 * @return MethodExecutionContext Modified or original method execution context after interception.
	 */
	public function intercept( MethodExecutionContext $methodExecutionContext ): mixed;
}

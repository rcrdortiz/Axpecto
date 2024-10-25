<?php

namespace Axpecto\Async;

use Axpecto\MethodExecution\MethodExecutionContext;
use Axpecto\MethodExecution\MethodExecutionHandler;

use Axpecto\Annotation\Annotation;
use Fiber;
use Exception;
use Throwable;

/**
 * Class AsyncMethodExecutionHandler
 *
 * Provides asynchronous interception capabilities using PHP Fibers. This handler allows
 * method execution to be paused and resumed asynchronously, simulating async behavior for
 * non-blocking operations.
 */
class AsyncMethodExecutionHandler implements MethodExecutionHandler {

	public function __construct( private readonly Runner $runner ) {
	}

	/**
	 * Intercepts a method execution within the provided chain asynchronously.
	 *
	 * @param MethodExecutionContext $methodExecutionContext
	 *
	 * @return mixed The result of the method execution, after handling the interception asynchronously.
	 * @throws Throwable
	 */
	public function intercept( MethodExecutionContext $methodExecutionContext ): mixed {
		$annotation = $methodExecutionContext->getAnnotation();

		if ( ! $annotation instanceof Async ) {
			return $methodExecutionContext->proceed();
		}

		if ( ! $annotation->fireAndForget ) {
			// @TODO Implement async/await pattern and scheduler.
			return $methodExecutionContext->proceed();
		}

		$class = $methodExecutionContext->className;
		$method = $methodExecutionContext->methodName;
		$arguments = serialize( $methodExecutionContext->arguments );

		$this->runner->fireAndForget( $class, $method, $arguments );

		return null;
	}
}
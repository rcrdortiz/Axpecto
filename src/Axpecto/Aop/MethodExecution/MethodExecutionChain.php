<?php

namespace Axpecto\Aop\MethodExecution;

use Axpecto\Aop\Annotation;
use Axpecto\Aop\MethodExecutionHandler;
use Axpecto\Collection\Concrete\Klist;
use Axpecto\Collection\Concrete\MutableKlist;
use Exception;

/**
 * Class MethodExecutionChain
 *
 * Manages the sequential execution of method annotations in a chain. Each annotation
 * can intercept, modify, or proceed with the method execution. If any annotations
 * have rollback or commit logic, these are applied as the method proceeds or fails.
 */
class MethodExecutionChain {
	/**
	 * @param Klist<Annotation> $queue The list of annotations to process in the chain.
	 */
	public function __construct(
		protected Klist $queue,
	) {
	}

	/**
	 * Proceeds with the next annotation in the chain or executes the method if no more annotations remain.
	 *
	 * This method processes the chain of annotations, allowing each one to intercept and modify the method's behavior.
	 * Rollback and commit logic is handled in case of exceptions or after successful execution, respectively.
	 *
	 * @param MethodExecutionContext $context The context of the method execution, including arguments and the method call itself.
	 *
	 * @return mixed The result of the method call or the annotation's intercept logic.
	 * @throws Exception If an error occurs during method execution or within an annotation handler.
	 */
	public function proceed( MethodExecutionContext $context ): mixed {
		// Retrieve the next annotation in the queue.
		$annotation = $this->queue->nextElement();

		// If no more annotations are left, invoke the actual method.
		if ( ! $annotation instanceof Annotation ) {
			return $context->invokeMethod(); // Calls the actual method
		}

		// Get the handler associated with the annotation.
		$handler = $annotation->getMethodExecutionHandler();

		// If the annotation does not have a valid handler, skip it and proceed.
		if ( ! $handler instanceof MethodExecutionHandler ) {
			return $this->proceed( $context );
		}

		// Intercept the method execution with the annotation handler.
		$context = $handler->intercept( $annotation, $context, $this );

		return $this->proceed( $context );
	}
}

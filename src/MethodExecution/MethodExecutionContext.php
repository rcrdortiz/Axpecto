<?php

namespace Axpecto\MethodExecution;

use Axpecto\Annotation\Annotation;
use Axpecto\Collection\Klist;
use Closure;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Class MethodExecutionContext
 *
 * Manages the execution of a method in the context of annotations that can intercept and modify its behavior.
 * The method execution context holds details about the class, method, arguments, and queue of annotations.
 */
class MethodExecutionContext {
	private ?Annotation $currentAnnotation = null;

	/**
	 * MethodExecutionContext constructor.
	 *
	 * @param string  $className  The fully qualified class name.
	 * @param string  $methodName The method name.
	 * @param Closure $methodCall The closure representing the method execution.
	 * @param array   $arguments  Arguments to pass to the method.
	 * @param Klist   $queue      Queue of annotations to process.
	 */
	public function __construct(
		public string $className,
		public string $methodName,
		public Closure $methodCall,
		public array $arguments,
		public Klist $queue,
	) {
	}

	/**
	 * Get the current annotation being processed.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @return Annotation|null
	 */
	public function getAnnotation(): ?Annotation {
		return $this->currentAnnotation;
	}

	/**
	 * Add or update an argument for the method execution.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param string $name  The argument name.
	 * @param mixed  $value The argument value.
	 */
	public function addArgument( string $name, mixed $value ): void {
		$this->arguments[ $name ] = $value;
	}

	/**
	 * Proceed with the method execution, processing annotations along the way.
	 *
	 * @return mixed The result of the method or the interception logic.
	 */
	public function proceed(): mixed {
		$annotation = $this->queue->current();
		$this->queue->next();
		$this->currentAnnotation = $annotation;

		if ( ! $annotation instanceof Annotation ) {
			// No more annotations, execute the actual method.
			return ( $this->methodCall )( ...$this->arguments );
		}

		$handler = $annotation->getMethodExecutionHandler();

		// If no handler is defined, skip and continue to the next annotation.
		if ( ! $handler instanceof MethodExecutionHandler ) {
			return $this->proceed();
		}

		// Intercept the method execution with the handler.
		return $handler->intercept( $this );
	}
}

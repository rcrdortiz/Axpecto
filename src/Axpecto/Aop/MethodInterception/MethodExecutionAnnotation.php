<?php

namespace Axpecto\Aop\MethodInterception;

use Attribute;
use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\MethodInterception\Build\MethodExecutionBuilder;
use Exception;

/**
 * Class MethodExecutionAnnotation
 *
 * Represents an abstract base class for method-level annotations in the AOP (Aspect-Oriented Programming) context.
 * This class enables dynamic behaviors to be applied to method executions using annotations, such as logging, validation,
 * or modifying method outputs.
 *
 * Each method annotation can be associated with a handler that defines how to intercept the method execution.
 * The handler is defined by the `handlerClass` property, which should implement `MethodExecutionAnnotationHandler`.
 * During runtime, the annotation's handler can be set and later used to intercept method execution through the AOP system.
 *
 * Usage Example:
 *
 * ```php
 * #[SomeCustomAnnotation(handlerClass: CustomHandler::class)]
 * public function someMethod() {
 *     // Method logic
 * }
 * ```
 *
 * @package Axpecto\Aop\MethodInterception
 */
#[Attribute( Attribute::TARGET_METHOD )]
abstract class MethodExecutionAnnotation extends BuildAnnotation {
	/**
	 * @var MethodExecutionAnnotationHandler|null The handler associated with this annotation.
	 */
	protected ?MethodExecutionAnnotationHandler $handler = null;

	/**
	 * MethodExecutionAnnotation constructor.
	 *
	 * @param string $handlerClass The fully qualified class name of the handler.
	 */
	public function __construct(
		public readonly string $handlerClass,
	) {
		parent::__construct( MethodExecutionBuilder::class );
	}

	/**
	 * Sets the handler for this annotation.
	 *
	 * @param MethodExecutionAnnotationHandler $handler The handler instance to be set.
	 *
	 * @return void
	 * @throws Exception If the provided handler does not match the expected handler class.
	 */
	public function setHandler( MethodExecutionAnnotationHandler $handler ): void {
		if ( ! $handler instanceof $this->handlerClass ) {
			throw new Exception(
				sprintf(
					'Invalid handler class. Expected %s but received %s.',
					$this->handlerClass,
					$handler::class,
				)
			);
		}

		$this->handler = $handler;
	}

	/**
	 * Retrieves the handler for this annotation.
	 *
	 * @return MethodExecutionAnnotationHandler|null The handler if set, otherwise null.
	 */
	public function getHandler(): ?MethodExecutionAnnotationHandler {
		return $this->handler;
	}
}

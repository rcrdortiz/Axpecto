<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\AnnotationReader;
use Axpecto\MethodExecution\MethodExecutionContext;
use Axpecto\Reflection\ReflectionUtils;
use Closure;
use ReflectionException;

/**
 * Class MethodExecutionProxy
 *
 * Handles the interception of method executions, applying annotations to control or augment the method's behavior.
 */
class MethodExecutionProxy {

	/**
	 * MethodExecutionProxy constructor.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param ReflectionUtils  $reflect The reflection utility instance for handling class/method reflection.
	 * @param AnnotationReader $reader  Reads annotations for the given class and method.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly AnnotationReader $reader,
	) {
	}

	/**
	 * Intercepts a method call and applies annotated behaviors.
	 *
	 * This method intercepts the specified method, retrieves its annotations, and creates a chain of execution that
	 * can modify the method's behavior. It allows annotations to control or augment method execution.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param string  $class      The fully qualified class name.
	 * @param string  $method     The method name to intercept.
	 * @param Closure $methodCall The original method's closure to call.
	 * @param array   $arguments  The arguments passed to the method.
	 *
	 * @return mixed The result of the method call or modified behavior based on annotations.
	 * @throws ReflectionException
	 */
	public function handle(
		string $class,
		string $method,
		Closure $methodCall,
		array $arguments,
	): mixed {
		// Get method annotations
		$annotations = $this->reader->getMethodExecutionAnnotations( $class, $method );

		// Resolve method arguments using reflection
		$mappedArguments = $this->reflect->mapValuesToArguments( $class, $method, $arguments );

		// Create and initialize the method execution context
		$context = new MethodExecutionContext(
			className:  $class,
			methodName: $method,
			methodCall: $methodCall,
			arguments:  $mappedArguments,
			queue:      $annotations,
		);

		// Delegate the execution to the context, which will handle proceeding through annotations
		return $context->proceed();
	}
}

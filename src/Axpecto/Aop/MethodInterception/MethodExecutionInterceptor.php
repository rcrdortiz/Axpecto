<?php

namespace Axpecto\Aop\MethodInterception;

use Closure;
use Axpecto\Collection\Concrete\Klist;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;

/**
 * Class MethodExecutionInterceptor
 *
 * This class intercepts method executions, applying annotations that handle additional logic for method calls.
 * It uses annotations and the reflection system to dynamically apply behaviors based on metadata.
 *
 * @package Axpecto\Aop\MethodInterception
 */
class MethodExecutionInterceptor {

	/**
	 * MethodExecutionInterceptor constructor.
	 *
	 * @param ReflectionUtils $reflect   The reflection utility instance for handling class/method reflection.
	 * @param Container       $container The dependency injection container for resolving annotation handlers.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly Container $container,
		private readonly ExecutionChainFactory $chainFactory,
	) {
	}

	/**
	 * Intercepts a method call and applies annotated behaviors.
	 *
	 * This method intercepts the specified method, retrieves its annotations, and creates a chain of execution that
	 * can modify the method's behavior. It allows annotations to control or augment method execution.
	 *
	 * @param string  $class      The fully qualified class name.
	 * @param string  $method     The method name to intercept.
	 * @param Closure $methodCall The original method's closure to call.
	 * @param array   $arguments  The arguments passed to the method.
	 *
	 * @return mixed The result of the method call or modified behavior based on annotations.
	 * @throws ReflectionException
	 */
	public function intercept(
		string $class,
		string $method,
		Closure $methodCall,
		array $arguments,
	): mixed {
		// Get method annotations of type MethodExecutionAnnotation
		$annotations = $this->reflect
			->getMethodAnnotations( $class, $method, MethodExecutionAnnotation::class )
			->filter( $this->hasAnnotationHandler( ... ) )  // Filter annotations with handlers
			->map( $this->bindAnnotationHandler( ... ) );    // Bind handler instances to annotations

		// Resolve method arguments using reflection
		$arguments = $this->reflect->getMethodArguments( $class, $method, $arguments );

		return $this->chainFactory->get(
			new Method( $class, $method, $methodCall, $arguments ),
			$annotations,
		)->proceed();
	}

	/**
	 * Binds the handler to the provided annotation.
	 *
	 * @param MethodExecutionAnnotation $annotation The annotation to bind a handler to.
	 *
	 * @return MethodExecutionAnnotation The annotation with the handler set.
	 * @throws Exception
	 */
	private function bindAnnotationHandler( MethodExecutionAnnotation $annotation ): MethodExecutionAnnotation {
		$handler = $this->container->get( $annotation->handlerClass );
		$annotation->setHandler( $handler );

		return $annotation;
	}

	/**
	 * Checks whether the annotation has an associated handler class.
	 *
	 * @param MethodExecutionAnnotation $annotation The annotation to check.
	 *
	 * @return bool True if the annotation has a handler, false otherwise.
	 */
	private function hasAnnotationHandler( MethodExecutionAnnotation $annotation ): bool {
		return $annotation->handlerClass !== null;
	}
}

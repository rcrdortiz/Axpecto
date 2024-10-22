<?php

namespace Axpecto\Aop\BuildHandler;

use Axpecto\Aop\AnnotationReader;
use Axpecto\Aop\MethodExecution\ExecutionChainFactory;
use Axpecto\Aop\MethodExecution\MethodExecutionContext;
use Axpecto\Reflection\ReflectionUtils;
use Closure;
use ReflectionException;

/**
 * Class MethodExecutionInterceptor
 *
 * This class intercepts method executions, applying annotations that handle additional logic for method calls.
 * It uses annotations and the reflection system to dynamically apply behaviors based on metadata.
 *
 * @package Axpecto\Aop\MethodInterception
 */
class MethodExecutionProxy {

	/**
	 * MethodExecutionInterceptor constructor.
	 *
	 * @param ReflectionUtils       $reflect The reflection utility instance for handling class/method reflection.
	 * @param AnnotationReader      $reader
	 * @param ExecutionChainFactory $chainFactory
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly AnnotationReader $reader,
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

		// Create the method context
		$methodExecutionContext = new MethodExecutionContext(
			className:  $class,
			methodName: $method,
			methodCall: $methodCall,
			arguments:  $mappedArguments
		);

		// Create the execution chain and proceed with the method call stack
		return $this->chainFactory->get( $annotations )->proceed( $methodExecutionContext );
	}
}

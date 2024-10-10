<?php

namespace Axpecto\Aop\MethodInterception\Build;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildAnnotationHandler;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Aop\BuildInterception\BuildOutput;
use Axpecto\Aop\MethodInterception\MethodExecutionInterceptor;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionException;

/**
 * Class MethodExecutionBuilder
 *
 * Handles building and intercepting method annotations for AOP-based method execution.
 * Generates the method signature and the corresponding implementation to ensure that
 * annotated methods are intercepted correctly.
 *
 * @package Axpecto\Aop\MethodInterception\Build
 */
class MethodExecutionBuilder implements BuildAnnotationHandler {
	/**
	 * MethodExecutionBuilder constructor.
	 *
	 * @param ReflectionUtils $reflect Reflection utility for analyzing classes and methods.
	 */
	public function __construct(
		protected readonly ReflectionUtils $reflect,
	) {
	}

	/**
	 * Intercepts a build chain, adding method interception logic to the output.
	 *
	 * @param BuildChain      $chain      The build chain to proceed with.
	 * @param BuildAnnotation $annotation The annotation being processed.
	 * @param string          $class      The class name containing the method.
	 * @param string|null     $method     The method name being intercepted.
	 * @param BuildOutput     $output     The current build output to modify.
	 *
	 * @return BuildOutput The modified build output.
	 * @throws ReflectionException
	 */
	public function intercept(
		BuildChain $chain,
		BuildAnnotation $annotation,
		string $class,
		?string $method,
		BuildOutput $output
	): BuildOutput {
		$reflectionMethod = $this->reflect->getReflectionClass( $class )->getMethod( $method );
		$hasReturn        = $reflectionMethod->hasReturnType() && $reflectionMethod->getReturnType()->getName() !== 'void';

		$output = $output->add(
			key:            $reflectionMethod->getName(),
			signature:      $this->reflect->getMethodDefinitionString( $reflectionMethod ),
			implementation: ( $hasReturn ? 'return ' : '' ) .
			                "\$this->interceptor->intercept( '$class', '$method', parent::$method(...), func_get_args() );",
			properties:     [
				                "#[" . Inject::class . "] private " . MethodExecutionInterceptor::class . " \$interceptor;",
			                ]
		);

		return $chain->proceed( $output );
	}
}

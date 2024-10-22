<?php

namespace Axpecto\Aop\BuildHandler;

use Axpecto\Aop\Annotation;
use Axpecto\Aop\Build\BuildChain;
use Axpecto\Aop\Build\BuildOutput;
use Axpecto\Aop\BuildHandler;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionException;

/**
 * Class MethodExecutionBuildHandler
 *
 * This class handles method annotations for AOP (Aspect-Oriented Programming) based method execution.
 * It intercepts the build chain and adds method signature and interception logic to the BuildOutput.
 *
 * @package Axpecto\Aop\BuildHandler
 */
class MethodExecutionBuildHandler implements BuildHandler {
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
	 * @param BuildChain  $chain      The build chain to proceed with.
	 * @param Annotation  $annotation The annotation being processed.
	 * @param BuildOutput $output     The current build output to modify.
	 *
	 * @return BuildOutput The modified build output.
	 * @throws ReflectionException If reflection on the method or class fails.
	 */
	public function intercept(
		BuildChain $chain,
		Annotation $annotation,
		BuildOutput $output,
	): BuildOutput {
		// Extract class and method from the annotation
		$class  = $annotation->getAnnotatedClass();
		$method = $annotation->getAnnotatedMethod();

		// Generate the method signature and implementation using reflection
		$signature      = $this->reflect->getMethodDefinitionString( $class, $method );
		$implementation = $this->generateImplementation( $class, $method );

		// Add the method to the output
		$output->addMethod( $method, $signature, $implementation );

		// Add the proxy property to the output
		$proxyProperty = $this->generateProxyProperty();
		$output->addProperty( MethodExecutionProxy::class, $proxyProperty );

		// Proceed to the next handler in the chain
		return $chain->proceed();
	}

	/**
	 * Generate the method implementation based on the return type.
	 *
	 * @param string $class  The class name being processed.
	 * @param string $method The method name being processed.
	 *
	 * @return string The method implementation string.
	 * @throws ReflectionException If reflection fails.
	 */
	protected function generateImplementation( string $class, string $method ): string {
		$returnStatement = $this->reflect->getReturnType( $class, $method ) !== 'void' ? 'return ' : '';

		return $returnStatement . "\$this->proxy->handle( '$class', '$method', parent::$method(...), func_get_args() );";
	}

	/**
	 * Generate the proxy property string.
	 *
	 * @return string The proxy property with an Inject annotation.
	 */
	protected function generateProxyProperty(): string {
		return "#[" . Inject::class . "] private " . MethodExecutionProxy::class . " \$proxy;";
	}
}

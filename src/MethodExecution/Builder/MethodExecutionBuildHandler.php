<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Reflection\ReflectionUtils;
use Override;
use ReflectionException;

/**
 * @psalm-suppress UnusedClass Used via annotation build system / DI container
 * Class MethodExecutionBuildHandler
 *
 * Handles method annotations for AOP (Aspect-Oriented Programming)-based method execution.
 * It intercepts the build chain and adds method signature and interception logic to the BuildOutput.
 */
class MethodExecutionBuildHandler implements BuildHandler {
	/**
	 * MethodExecutionBuildHandler constructor.
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
	 * @param Annotation   $annotation The annotation being processed.
	 * @param BuildContext $context    The current build context to modify.
	 *
	 * @throws ReflectionException If reflection on the method or class fails.
	 */
	#[Override]
	public function intercept( Annotation $annotation, BuildContext $context ): void {
		$class  = $annotation->getAnnotatedClass();
		$method = $annotation->getAnnotatedMethod();

		// Generate the method signature and implementation using reflection.
		$signature      = $this->reflect->getMethodDefinitionString( $class, $method );
		$implementation = $this->generateImplementation( $class, $method );

		// Add the method and proxy property to the context output.
		$context->addMethod( $method, $signature, $implementation );
		$context->addProperty( MethodExecutionProxy::class, $this->generateProxyProperty() );
	}

	/**
	 * Generates the method implementation based on the return type.
	 *
	 * @param string $class  The class name being processed.
	 * @param string $method The method name being processed.
	 *
	 * @return string The method implementation string.
	 * @throws ReflectionException If reflection fails.
	 */
	protected function generateImplementation( string $class, string $method ): string {
		$returnStatement = $this->reflect->getReturnType( $class, $method ) !== 'void' ? 'return ' : '';

		return $returnStatement . "\$this->proxy->handle('$class', '$method', parent::$method(...), func_get_args());";
	}

	/**
	 * Generates the proxy property string.
	 *
	 * @return string The proxy property with an Inject annotation.
	 */
	protected function generateProxyProperty(): string {
		return "#[" . Inject::class . "] private " . MethodExecutionProxy::class . " \$proxy;";
	}
}

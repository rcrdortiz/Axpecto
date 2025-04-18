<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
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
	const PROXY_PROPERTY_NAME = 'proxy';

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
	 * @throws Exception
	 */
	#[Override]
	public function intercept( Annotation $annotation, BuildContext $context ): void {
		$class  = $annotation->getAnnotatedClass();
		$method = $annotation->getAnnotatedMethod();

		// Define properties and add them as injectable dependencies.
		$context->injectProperty( self::PROXY_PROPERTY_NAME, MethodExecutionProxy::class );

		// Generate the method signature and implementation using reflection.
		$methodSignature = $this->reflect->getMethodDefinitionString( $class, $method );
		$returnStatement = $this->reflect->getReturnType( $class, $method ) !== 'void' ? 'return ' : '';
		$implementation  = $returnStatement . "\$this->" . self::PROXY_PROPERTY_NAME
		                   . "->handle('$class', '$method', parent::$method(...), func_get_args());";

		// Add the method and proxy property to the context output.
		$context->addMethod( $method, $methodSignature, $implementation );
	}
}

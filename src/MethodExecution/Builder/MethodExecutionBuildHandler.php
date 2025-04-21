<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildOutput;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Code\MethodCodeGenerator;
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
	const string PROXY_PROPERTY_NAME = 'proxy';

	/**
	 * MethodExecutionBuildHandler constructor.
	 *
	 * @param MethodCodeGenerator $code
	 */
	public function __construct(
		protected readonly MethodCodeGenerator $code,
	) {
	}

	/**
	 * Intercepts a build chain, adding method interception logic to the output.
	 *
	 * @param Annotation $annotation The annotation being processed.
	 * @param BuildOutput $buildOutput The current build context to modify.
	 *
	 * @throws ReflectionException If reflection on the method or class fails.
	 * @throws Exception
	 */
	#[Override]
	public function intercept( Annotation $annotation, BuildOutput $buildOutput ): void {
		$class  = $annotation->getAnnotatedClass();
		$method = $annotation->getAnnotatedMethod();

		// Define properties and add them as injectable dependencies.
		$buildOutput->injectProperty( self::PROXY_PROPERTY_NAME, MethodExecutionProxy::class );

		// Generate the method signature and implementation using reflection.
		$methodSignature = $this->code->implementMethodSignature( $class, $method );
		$implementation  = "return \$this->" . self::PROXY_PROPERTY_NAME . "->handle('$class', '$method', parent::$method(...), func_get_args());";

		// Add the method and proxy property to the context output.
		$buildOutput->addMethod( $method, $methodSignature, $implementation );
	}
}

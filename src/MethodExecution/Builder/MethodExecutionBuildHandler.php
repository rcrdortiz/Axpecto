<?php

namespace Axpecto\MethodExecution\Builder;

use Axpecto\Annotation\BuildAnnotation;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\ClassBuilder\BuildOutput;
use Axpecto\Code\AnnotationCodeGenerator;
use Axpecto\Code\MethodCodeGenerator;
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
	const string PROXY_PROPERTY_NAME = 'proxy';

	/**
	 * MethodExecutionBuildHandler constructor.
	 *
	 * @param MethodCodeGenerator $methodCoder
	 * @param AnnotationCodeGenerator $annotationCoder
	 * @param ReflectionUtils $reflection
	 */
	public function __construct(
		protected readonly MethodCodeGenerator $methodCoder,
		protected readonly AnnotationCodeGenerator $annotationCoder,
		protected readonly ReflectionUtils $reflection,
	) {
	}

	/**
	 * Intercepts a build chain, adding method interception logic to the output.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param BuildAnnotation $annotation The annotation being processed.
	 * @param BuildOutput $buildOutput The current build context to modify.
	 *
	 * @throws ReflectionException If reflection on the method or class fails.
	 * @throws Exception
	 */
	#[Override]
	public function intercept( BuildAnnotation $annotation, BuildOutput $buildOutput ): void {
		$class  = $annotation->getAnnotatedClass();
		$method = $annotation->getAnnotatedMethod();

		$isAbstract = $this->reflection->getClassMethod( $class, $method )->isAbstract();

		if ( $isAbstract ) {
			// Interfaces don't have parent implementations, we add the annotation back and wait for the next build pass.
			$annotationCode = $this->annotationCoder->serializeAnnotation( $annotation );
			$buildOutput->annotateMethod( $method, $annotationCode );
			return;
		}

		// Define properties and add them as injectable dependencies.
		$buildOutput->injectProperty( self::PROXY_PROPERTY_NAME, MethodExecutionProxy::class );

		// Generate the method signature and implementation using reflection.
		$methodSignature = $this->methodCoder->implementMethodSignature( $class, $method );
		$implementation  = "return \$this->" . self::PROXY_PROPERTY_NAME . "->handle('$class', '$method', parent::$method(...), func_get_args());";

		// Add the method and proxy property to the context output.
		$buildOutput->addMethod( $method, $methodSignature, $implementation );
	}
}

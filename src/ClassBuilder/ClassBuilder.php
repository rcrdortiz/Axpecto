<?php

namespace Axpecto\ClassBuilder;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationService;
use Axpecto\Annotation\BuildAnnotation;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;

/**
 * Class ClassBuilder
 *
 * This class is responsible for dynamically building and proxying classes with Aspect-Oriented Programming (AOP) capabilities.
 *
 * @template T
 */
class ClassBuilder {

	/**
	 * @param ReflectionUtils $reflect Utility for handling reflection of classes, methods, and properties.
	 * @param array<string, string> $builtClasses Stores already built classes to avoid duplication.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly AnnotationService $annotationService,
		private array $builtClasses = [],
	) {
	}

	/**
	 * Registers an AOP proxied class and returns the proxy class name.
	 *
	 * @param class-string<T> $class The class name to build.
	 *
	 * @return string The name of the proxied class.
	 * @throws ReflectionException
	 * @throws ClassAlreadyBuiltException
	 * @throws Exception
	 */
	public function build( string $class, int $pass = 0, ?string $extends = null ): string {
		// “current” is the class we’re actually building this pass
		$current = $extends ?? $class;

		// Check if the class has already been built
		if ( isset( $this->builtClasses[ $current ] ) ) {
			throw new ClassAlreadyBuiltException( $current );
		}

		// Get all the Build annotations for the class and its methods
		$buildAnnotations = $this->annotationService->getAllAnnotations( $current, BuildAnnotation::class );

		// Create and proceed with the build chain
		$context = new BuildOutput( $current );
		$buildAnnotations->foreach( fn( BuildAnnotation $a ) => $a->getBuilder()?->intercept( $a, $context ) );

		// Annotate the methods with the existing annotations, we exclude BuildAnnotations.
		$context->methods->foreach( fn( string $methodName ) => $this->annotationService->getMethodAnnotations( $current, $methodName, Annotation::class )
		                                                                                ->filter( fn( Annotation $a ) => ! $a instanceof BuildAnnotation )
		                                                                                ->foreach( fn( Annotation $a ) => $context->annotateMethod( $methodName, $a::class ) )
		);

		// If the build output is empty, return the original class
		if ( $context->isEmpty() ) {
			return $current;
		}

		// Generate and evaluate the proxy class
		$proxiedClass = $this->buildClass( $class, $context, $pass, $extends );

		// Cache the built class
		$this->builtClasses[ $current ] = $proxiedClass;

		// Check if the class has any Build annotations and trigger a new build pass.
		if ( $this->annotationService->getAllAnnotations( $proxiedClass, BuildAnnotation::class )->isNotEmpty() ) {
			return $this->build( $current, ++$pass, $proxiedClass );
		}

		// Return the proxy class name
		return $proxiedClass;
	}

	/**
	 * Generates the proxy class code based on the build output.
	 *
	 * This method constructs the class declaration and body, including properties and methods as defined by the build output.
	 * It also evaluates the generated class code dynamically using `eval`.
	 *
	 * @param string $class The original class name to be proxied.
	 * @param BuildOutput $buildOutput The output from the build process, containing properties and methods.
	 *
	 * @return string The name of the generated proxy class.
	 * @throws ReflectionException
	 */
	private function buildClass( string $class, BuildOutput $buildOutput, int $pass, ?string $extends = null ): string {
		// Generate a unique proxy class name by replacing backslashes in the class name.
		$proxiedClassName = str_replace( "\\", '_', $class ) ."__x$pass";

		// Define whether the proxy class extends or implements the original class.
		$inheritanceType = $this->reflect->isInterface( $class ) ? 'implements' : 'extends';

		$traits = '';
		if ( $buildOutput->traits->isNotEmpty() ) {
			$traits = "\tuse " . $buildOutput->traits->join( "," ) . ';';
		}

		$methods = $buildOutput->methods->map( fn( $method, $code ) => [ $method => $buildOutput->getMethodAnnotations( $method )->join() . $code ] );

		// Construct the full class definition.
		$proxiedClass = sprintf(
			"\nclass %s %s %s {\n%s\n%s\n\n%s\n}",
			$proxiedClassName,
			$inheritanceType,
			$extends ?? $class,
			$traits,
			"\t" . $buildOutput->properties->join( "\n\t" ),
			"\t" . $methods->join( "\n\t" ),
		);

		/* @TODO Replace with a component that allows for different behaviors besides eval. */
		// Dynamically evaluate the class definition using eval.
		if ( defined( "DEBUG_CLASS_BUILD_OUTPUT" ) && DEBUG_CLASS_BUILD_OUTPUT ) {
			var_dump( $proxiedClass );
		}

		eval( $proxiedClass );

		return $proxiedClassName;
	}
}

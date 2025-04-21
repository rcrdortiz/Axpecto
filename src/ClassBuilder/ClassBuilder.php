<?php

namespace Axpecto\ClassBuilder;

use Axpecto\Annotation\BuildAnnotation;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionException;
use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationReader;

/**
 * Class ClassBuilder
 *
 * This class is responsible for dynamically building and proxying classes with Aspect-Oriented Programming (AOP) capabilities.
 *
 * @template T
 */
class ClassBuilder {

	/**
	 * @param ReflectionUtils       $reflect      Utility for handling reflection of classes, methods, and properties.
	 * @param array<string, string> $builtClasses Stores already built classes to avoid duplication.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly AnnotationReader $reader,
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
	 */
	public function build( string $class ): string {
		// Check if the class has already been built
		if ( isset( $this->builtClasses[ $class ] ) ) {
			throw new ClassAlreadyBuiltException( $class );
		}

		// Get all the Build annotations for the class and its methods
		$buildAnnotations = $this->reader->getAllAnnotations( $class, BuildAnnotation::class );

		// Create and proceed with the build chain
		$context = new BuildOutput( $class );
		$buildAnnotations->foreach( fn( Annotation $a ) => $a->getBuilder()?->intercept( $a, $context ) );

		// If the build output is empty, return the original class
		if ( $context->isEmpty() ) {
			return $class;
		}

		// Generate and evaluate the proxy class
		$proxiedClass = $this->generateProxyClass( $class, $context );

		// Cache the built class
		$this->builtClasses[ $class ] = $proxiedClass;

		// Return the proxy class name
		return $proxiedClass;
	}

	/**
	 * Generates the proxy class code based on the build output.
	 *
	 * This method constructs the class declaration and body, including properties and methods as defined by the build output.
	 * It also evaluates the generated class code dynamically using `eval`.
	 *
	 * @param string       $class       The original class name to be proxied.
	 * @param BuildOutput $buildOutput The output from the build process, containing properties and methods.
	 *
	 * @return string The name of the generated proxy class.
	 * @throws ReflectionException
	 */
	private function generateProxyClass( string $class, BuildOutput $buildOutput ): string {
		// Generate a unique proxy class name by replacing backslashes in the class name.
		$proxiedClassName = str_replace( "\\", '_', $class ) . 'Proxy';

		// Define whether the proxy class extends or implements the original class.
		$inheritanceType = $this->reflect->isInterface( $class ) ? 'implements' : 'extends';

		$traits = '';
		if ( $buildOutput->traits->isNotEmpty() ) {
			$traits = "\tuse " . $buildOutput->traits->join( "," ) . ';';
		}

		// Construct the full class definition.
		$proxiedClass = sprintf(
			"\nclass %s %s %s {\n%s\n%s\n%s\n}",
			$proxiedClassName,
			$inheritanceType,
			$class,
			$traits,
			"\t" . $buildOutput->properties->join( "\n\t" ),
			"\t" . $buildOutput->methods->join( "\n\t" ),
		);

		/* @TODO Replace with a component that allows for different behaviors besides eval. */
		// Dynamically evaluate the class definition using eval.
		eval( $proxiedClass );

		return $proxiedClassName;
	}
}

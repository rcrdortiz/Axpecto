<?php

namespace Axpecto\Build;

use Axpecto\Aop\AnnotationReader;
use Axpecto\Aop\Build\BuildChainFactory;
use Axpecto\Aop\Build\BuildOutput;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Reflection\ReflectionUtils;
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
	 * @param ReflectionUtils       $reflect           Utility for handling reflection of classes, methods, and properties.
	 * @param BuildChainFactory     $buildChainFactory Factory for creating build chains.
	 * @param array<string, string> $builtClasses      Stores already built classes to avoid duplication.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly BuildChainFactory $buildChainFactory,
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
		$buildAnnotations = $this->reader->getAllBuildAnnotations( $class );

		// Create and proceed with the build chain
		$buildOutput = $this->buildChainFactory->get( $buildAnnotations )->proceed();

		// If the build output is empty, return the original class
		if ( $buildOutput->isEmpty() ) {
			return $class;
		}

		// Generate and evaluate the proxy class
		$proxiedClass = $this->generateProxyClass( $class, $buildOutput );

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
	 * @param string      $class       The original class name to be proxied.
	 * @param BuildOutput $buildOutput The output from the build process, containing properties and methods.
	 *
	 * @return string The name of the generated proxy class.
	 * @throws ReflectionException
	 */
	private function generateProxyClass( string $class, BuildOutput $buildOutput ): string {
		// Generate a unique proxy class name by replacing backslashes in the class name.
		$proxiedClassName = str_replace( "\\", '_', $class );

		// Define whether the proxy class extends or implements the original class.
		$inheritanceType = $this->reflect->isInterface( $class ) ? 'implements' : 'extends';

		// Construct the full class definition.
		$proxiedClass = sprintf(
			"\nclass %s %s %s {\n%s\n%s\n}",
			$proxiedClassName,
			$inheritanceType,
			$class,
			"\t" . $buildOutput->properties->join( "\n\t" ),
			"\t" . $buildOutput->methods->join( "\n\t" ),
		);

		/* @TODO Replace with a component that allows for different behaviors besides eval. */
		// Dynamically evaluate the class definition using eval.
		eval( $proxiedClass );

		return $proxiedClassName;
	}
}

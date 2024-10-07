<?php

namespace Axpecto\Aop;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Aop\BuildInterception\BuildChainFactory;
use Axpecto\Aop\BuildInterception\BuildOutput;
use Axpecto\Aop\Exception\ClassAlreadyBuiltException;
use Axpecto\Container\Container;
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
	 * @param ReflectionUtils       $reflect           Utility for handling reflection of classes, methods, and properties.
	 * @param Container             $container         Dependency Injection Container for managing instances.
	 * @param BuildChainFactory     $buildChainFactory Factory for creating build chains.
	 * @param array<string, string> $builtClasses      Stores already built classes to avoid duplication.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly Container $container,
		private readonly BuildChainFactory $buildChainFactory,
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
		if ( isset( $this->builtClasses[ $class ] ) ) {
			throw new ClassAlreadyBuiltException( $class );
		}

		// Get class-level annotations and apply them
		$classAnnotations = $this->reflect->getClassAnnotations( $class, BuildAnnotation::class );
		$classAnnotations->foreach( $this->bindAnnotationHandler( ... ) );
		$buildOutput = $this->buildChainFactory->get( $classAnnotations, $class )->proceed();

		// Process annotated methods in the class
		$methods = $this->reflect->getAnnotatedMethods( $class, BuildAnnotation::class );

		foreach ( $methods as $method ) {
			$methodAnnotations = $this->reflect
				->getMethodAnnotations( $class, $method->getName(), BuildAnnotation::class )
				->map( $this->bindAnnotationHandler( ... ) );

			$buildOutput = $this->buildChainFactory->get( $methodAnnotations, $class, $method->getName() )->proceed( $buildOutput );
		}

		// If no output from annotations, return original class
		if ( ! $buildOutput->hasOutput() ) {
			return $class;
		}

		// Generate and evaluate the proxy class
		$reflection      = $this->reflect->getReflectionClass( $class );
		$inheritanceType = $reflection->isInterface() ? 'implements' : 'extends';
		$className       = str_replace( "\\", '_', $class );
		$proxiedClass    = $this->generateProxyClass( $class, $inheritanceType, $className, $buildOutput );

		eval( $proxiedClass );

		// Cache the built class
		$this->builtClasses[ $class ] = $className;

		return $className;
	}

	/**
	 * Binds the annotation handler by setting its builder, if applicable.
	 *
	 * @param BuildAnnotation $annotation The build annotation.
	 *
	 * @return BuildAnnotation The annotation with the builder set.
	 * @throws Exception
	 */
	private function bindAnnotationHandler( BuildAnnotation $annotation ): BuildAnnotation {
		if ( $annotation->builderClass ) {
			$annotation->setBuilder( $this->container->get( $annotation->builderClass ) );
		}

		return $annotation;
	}

	/**
	 * Generates the proxy class code including uses, properties, and methods.
	 *
	 * @param string      $class           The original class name.
	 * @param string      $inheritanceType Either 'extends' or 'implements' based on whether the class is an interface or a class.
	 * @param string      $className       The generated proxy class name.
	 * @param BuildOutput $buildOutput     The output from the build process, containing properties, methods, and use statements.
	 *
	 * @return string The generated proxy class as a string.
	 */
	private function generateProxyClass( string $class, string $inheritanceType, string $className, BuildOutput $buildOutput ): string {
		return sprintf(
			"\n%s\n\nclass %s %s %s {\n%s\n%s\n}",
			$this->getBuildUses( $buildOutput ),
			$className,
			$inheritanceType,
			$class,
			$this->getBuildProperties( $buildOutput ),
			$this->getBuildMethods( $buildOutput )
		);
	}

	/**
	 * Generates method implementations for the proxy class.
	 *
	 * @param BuildOutput $buildOutput The output containing the methods.
	 *
	 * @return string The methods formatted for the class.
	 */
	private function getBuildMethods( BuildOutput $buildOutput ): string {
		if ( ! $buildOutput->hasOutput() ) {
			return '';
		}

		return "\t" . implode( "\n\t", array_unique( $buildOutput->methods ) );
	}

	/**
	 * Generates properties for the proxy class.
	 *
	 * @param BuildOutput $buildOutput The output containing the properties.
	 *
	 * @return string The properties formatted for the class.
	 */
	private function getBuildProperties( BuildOutput $buildOutput ): string {
		if ( ! $buildOutput->hasOutput() ) {
			return '';
		}

		return "\t" . implode( "\n\t", array_unique( $buildOutput->properties ) );
	}

	/**
	 * Generates 'use' statements for the proxy class.
	 *
	 * @param BuildOutput $buildOutput The output containing the use statements.
	 *
	 * @return string The use statements formatted for the class.
	 */
	private function getBuildUses( BuildOutput $buildOutput ): string {
		if ( ! $buildOutput->hasOutput() ) {
			return '';
		}

		return implode( "\n", array_map( fn( $use ) => "use $use;", array_unique( $buildOutput->useStatements ) ) );
	}
}

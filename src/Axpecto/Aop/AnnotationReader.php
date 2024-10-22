<?php

namespace Axpecto\Aop;

use Axpecto\Collection\Concrete\Klist;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;
use ReflectionMethod;

/**
 * Class AnnotationReader
 *
 * This class is responsible for reading annotations on classes and methods, specifically
 * for use in an Aspect-Oriented Programming (AOP) context. It handles fetching annotations,
 * including build-related annotations, and supports dependency injection for these annotations.
 *
 * @template T
 */
class AnnotationReader {

	/**
	 * Constructor for AnnotationReader.
	 *
	 * @param Container       $container The dependency injection container.
	 * @param ReflectionUtils $reflect   Utility for handling reflection of classes and methods.
	 */
	public function __construct(
		private readonly Container $container,
		private readonly ReflectionUtils $reflect,
	) {
	}

	/**
	 * Fetches annotations for a specific method.
	 *
	 * @param class-string<T> $class           The fully qualified class name.
	 * @param string          $method          The method name.
	 * @param string          $annotationClass The annotation class to filter.
	 *
	 * @return Klist<Annotation> A list of annotations for the method.
	 * @throws ReflectionException|Exception
	 */
	public function getMethodAnnotations( string $class, string $method, string $annotationClass = Annotation::class ): Klist {
		return $this->mapAttributesToAnnotations(
			attributes:      $this->reflect->getMethodAttributes( $class, $method ),
			annotationClass: $annotationClass
		);
	}

	/**
	 * Fetches build-related annotations for a method.
	 *
	 * @param class-string<T> $class           The fully qualified class name.
	 * @param string          $method          The method name.
	 * @param string          $annotationClass The annotation class to filter.
	 *
	 * @return Klist<Annotation> A list of build annotations for the method.
	 * @throws ReflectionException
	 */
	public function getMethodExecutionAnnotations( string $class, string $method, string $annotationClass = Annotation::class ): Klist {
		return $this->getMethodAnnotations( $class, $method, $annotationClass )
		            ->filter( fn( Annotation $annotation ) => $annotation->isMethodExecutionAnnotation() )
		            ->map( fn( Annotation $annotation ) => $annotation->setAnnotatedClass( $class )->setAnnotatedMethod( $method ) );
	}

	/**
	 * Fetches build-related annotations for a method.
	 *
	 * @param class-string<T> $class           The fully qualified class name.
	 * @param string          $method          The method name.
	 * @param string          $annotationClass The annotation class to filter.
	 *
	 * @return Klist<Annotation> A list of build annotations for the method.
	 * @throws ReflectionException
	 */
	public function getMethodBuildAnnotations( string $class, string $method, string $annotationClass = Annotation::class ): Klist {
		return $this->getMethodAnnotations( $class, $method, $annotationClass )
		            ->filter( fn( Annotation $annotation ) => $annotation->isBuildAnnotation() )
		            ->map( fn( Annotation $annotation ) => $annotation->setAnnotatedClass( $class )->setAnnotatedMethod( $method ) );
	}

	/**
	 * Fetches all build-related annotations for a class, including its methods.
	 *
	 * @param class-string<T> $class           The fully qualified class name.
	 * @param string          $annotationClass The annotation class to filter.
	 *
	 * @return Klist<Annotation> A list of all build annotations for the class.
	 * @throws ReflectionException
	 */
	public function getAllBuildAnnotations( string $class, string $annotationClass = Annotation::class ): Klist {
		return $this->reflect
			->getAnnotatedMethods( $class )
			->map( fn( ReflectionMethod $method ) => $this->getMethodBuildAnnotations( $class, $method->getName(), $annotationClass ) )
			->flatten()
			->merge( $this->getClassBuildAnnotations( $class, $annotationClass ) );
	}

	/**
	 * Fetches annotations for a class.
	 *
	 * @param class-string<T> $class           The fully qualified class name.
	 * @param string          $annotationClass The annotation class to filter.
	 *
	 * @return Klist<Annotation> A list of annotations for the class.
	 * @throws ReflectionException|Exception
	 */
	public function getClassAnnotations( string $class, string $annotationClass = Annotation::class ): Klist {
		return $this->mapAttributesToAnnotations(
			attributes:      $this->reflect->getClassAttributes( $class ),
			annotationClass: $annotationClass
		);
	}

	/**
	 * Fetches build-related annotations for a class.
	 *
	 * @param class-string<T> $class           The fully qualified class name.
	 * @param string          $annotationClass The annotation class to filter.
	 *
	 * @return Klist<Annotation> A list of build annotations for the class.
	 * @throws ReflectionException|Exception
	 */
	public function getClassBuildAnnotations( string $class, string $annotationClass = Annotation::class ): Klist {
		return $this->getClassAnnotations( $class, $annotationClass )
		            ->filter( fn( Annotation $annotation ) => $annotation->isBuildAnnotation() )
		            ->map( fn( Annotation $annotation ) => $annotation->setAnnotatedClass( $class ) );
	}

	/**
	 * Maps attributes to annotations and applies property injection.
	 *
	 * @param Klist  $attributes      A list of attributes.
	 * @param string $annotationClass The annotation class to filter.
	 *
	 * @return Klist A list of filtered and injected annotations.
	 * @throws Exception
	 */
	private function mapAttributesToAnnotations( Klist $attributes, string $annotationClass ): Klist {
		return $attributes
			->filter( fn( $annotation ) => $annotation instanceof $annotationClass )
			->foreach( fn( $annotation ) => $this->container->applyPropertyInjection( $annotation ) );
	}
}

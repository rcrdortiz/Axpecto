<?php

namespace Axpecto\Annotation;

use Axpecto\Collection\Klist;
use Axpecto\Container\DependencyResolver;
use ReflectionException;

/**
 * Reads PHP8 attributes and turns them into AOP-style Annotation instances,
 * filtering by class vs. method targets and injecting their properties via DI.
 *
 * @template A of Annotation
 * @psalm-consistent-constructor
 */
class AnnotationService {

	public function __construct(
		private readonly AnnotationReader $reader,
		private readonly DependencyResolver $dependencyResolver,
	) {
	}

	/**
	 * Fetch all annotations of a given type on a class.
	 *
	 * @template T
	 * @param class-string<T> $class
	 * @param class-string<A> $annotationClass
	 *
	 * @return Klist<A>
	 * @throws ReflectionException
	 */
	public function getClassAnnotations(
		string $class,
		string $annotationClass,
	): Klist {
		return $this->reader->getClassAnnotations( $class, $annotationClass )
		                    ->foreach( fn( Annotation $a ) => $this->dependencyResolver->applyPropertyInjection( $a ) );
	}

	/**
	 * Fetch all annotations of a given type on a method.
	 *
	 * @template T
	 * @param class-string<T> $class
	 * @param string $method
	 * @param class-string<A> $annotationClass
	 *
	 * @return Klist<T>
	 * @throws ReflectionException
	 */
	public function getMethodAnnotations(
		string $class,
		string $method,
		string $annotationClass,
	): Klist {
		return $this->reader->getMethodAnnotations( $class, $method, $annotationClass )
		                    ->foreach( fn( Annotation $a ) => $this->dependencyResolver->applyPropertyInjection( $a ) );
	}

	/**
	 * Fetch both class‑level and method‑level annotations of a given type.
	 *
	 * @template T
	 * @param class-string<T> $class
	 * @param class-string<A> $annotationClass
	 *
	 * @return Klist<T>
	 * @throws ReflectionException
	 */
	public function getAllAnnotations(
		string $class,
		string $annotationClass = Annotation::class
	): Klist {
		return $this->reader->getAllAnnotations( $class, $annotationClass )
		                    ->foreach( fn( Annotation $a ) => $this->dependencyResolver->applyPropertyInjection( $a ) );
	}

	/**
	 * Fetch all annotations of a given type on one of a method’s parameters.
	 *
	 * @template T
	 * @param class-string<T> $class
	 * @param string $method
	 * @param string $parameterName
	 * @param class-string<A> $annotationClass
	 *
	 * @return Klist<A>
	 * @throws ReflectionException
	 */
	public function getParameterAnnotations(
		string $class,
		string $method,
		string $parameterName,
		string $annotationClass,
	): Klist {
		return $this->reader->getParameterAnnotations( $class, $method, $parameterName, $annotationClass )
		                    ->foreach( fn( Annotation $a ) => $this->dependencyResolver->applyPropertyInjection( $a ) );
	}

	/**
	 * Fetch a single annotation of a given type on a property.
	 *
	 * @template T
	 * @param class-string<T> $class
	 * @param string $property
	 * @param class-string<A> $annotationClass
	 *
	 * @return A|null
	 * @throws ReflectionException
	 */
	public function getPropertyAnnotation(
		string $class,
		string $property,
		string $annotationClass = Annotation::class
	): mixed {
		$annotation = $this->reader->getPropertyAnnotation( $class, $property, $annotationClass );
		$this->dependencyResolver->applyPropertyInjection( $annotation );

		return $annotation;
	}
}
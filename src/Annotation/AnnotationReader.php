<?php

declare( strict_types=1 );

namespace Axpecto\Annotation;

use Axpecto\Collection\Klist;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionAttribute;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

/**
 * AnnotationReader
 *
 * Reads PHP 8 attributes and turns them into Annotation instances,
 * filtering by class, and annotating each instance
 * with its declaring class and/or method name.
 *
 * @template A of Annotation
 * @psalm-consistent-constructor
 */
class AnnotationReader {
	/**
	 * @param ReflectionUtils $reflection
	 *   Used to fetch native PHP ReflectionAttribute instances.
	 */
	public function __construct(
		private readonly ReflectionUtils $reflection
	) {
	}

	/**
	 * Fetch all annotations of the given type on a class.
	 *
	 * @template T of Annotation
	 * @param class-string<A> $class
	 * @param class-string<T> $annotationClass
	 *
	 * @return Klist<T>  A list of instantiated annotations, each with its
	 *                   ->setAnnotatedClass($class) already applied.
	 *
	 * @throws ReflectionException
	 */
	public function getClassAnnotations(
		string $class,
		string $annotationClass
	): Klist {
		return $this->reflection
			->getClassAttributes( $class )
			->filter( fn( Annotation $ann ) => $ann instanceof $annotationClass )
			->map( fn( Annotation $ann ) => $ann->setAnnotatedClass( $class ) );
	}

	/**
	 * Fetch all annotations of the given type on a specific method.
	 *
	 * @template T of Annotation
	 * @param class-string<A> $class
	 * @param string $method
	 * @param class-string<T> $annotationClass
	 *
	 * @return Klist<T>  A list of instantiated annotations, each with
	 *                   ->setAnnotatedClass($class)
	 *                   and ->setAnnotatedMethod($method) applied.
	 *
	 * @throws ReflectionException
	 */
	public function getMethodAnnotations(
		string $class,
		string $method,
		string $annotationClass
	): Klist {
		return $this->reflection
			->getMethodAttributes( $class, $method )
			->filter( fn( Annotation $ann ) => $ann instanceof $annotationClass )
			->map( fn( Annotation $ann ) => $ann
				->setAnnotatedClass( $class )
				->setAnnotatedMethod( $method )
			);
	}

	/**
	 * Fetch both class-level and method-level annotations of a given type.
	 *
	 * @template T of Annotation
	 * @param class-string<A> $class
	 * @param class-string<T> $annotationClass
	 *
	 * @return Klist<T>  All matching annotations on the class itself
	 *                   and on any of its methods.
	 *
	 * @throws ReflectionException
	 */
	public function getAllAnnotations(
		string $class,
		string $annotationClass,
	): Klist {
		$classAnns = $this->getClassAnnotations( $class, $annotationClass );

		$methodAnns = $this->reflection
			->getAnnotatedMethods( $class, $annotationClass )
			->map( fn( ReflectionMethod $m ) => $this->getMethodAnnotations( $class, $m->getName(), $annotationClass ) )
			->flatten();

		return $classAnns->merge( $methodAnns );
	}

	/**
	 * Fetch all annotations of the given type on a single method parameter.
	 *
	 * @template T of Annotation
	 * @param class-string<A> $class
	 * @param string $method
	 * @param string $parameterName
	 * @param class-string<T> $annotationClass
	 *
	 * @return Klist<T>  A list (possibly empty) of annotations on that parameter,
	 *                   each with ->setAnnotatedClass() and ->setAnnotatedMethod().
	 *
	 * @throws ReflectionException
	 */
	public function getParameterAnnotations(
		string $class,
		string $method,
		string $parameterName,
		string $annotationClass
	): Klist {
		$param = listFrom( $this->reflection->getClassMethod( $class, $method )->getParameters() )
			->filter( fn( ReflectionParameter $p ) => $p->getName() === $parameterName )
			->firstOrNull();

		if ( $param === null ) {
			return emptyList();
		}

		return listFrom( $param->getAttributes() )
			->map( fn( ReflectionAttribute $attr ) => $attr->newInstance() )
			->filter( fn( $inst ) => $inst instanceof $annotationClass )
			->map( fn( Annotation $ann ) => $ann
				->setAnnotatedClass( $class )
				->setAnnotatedMethod( $method )
			);
	}

	/**
	 * Fetch exactly one annotation of the given type on a class property.
	 *
	 * @template T of Annotation
	 * @param class-string<object> $class
	 * @param string $property
	 * @param class-string<T> $annotationClass
	 *
	 * @return T The first matching annotation, or null if none.
	 *
	 * @throws ReflectionException
	 */
	public function getPropertyAnnotation(
		string $class,
		string $property,
		string $annotationClass = Annotation::class
	): ?Annotation {
		$attrs = $this->reflection
			->getReflectionClass( $class )
			->getProperty( $property )
			->getAttributes();

		return listFrom( $attrs )
			->map( fn( ReflectionAttribute $attr ) => $attr->newInstance() )
			->filter( fn( $inst ) => $inst instanceof $annotationClass )
			->firstOrNull()
			?->setAnnotatedClass( $class );
	}
}

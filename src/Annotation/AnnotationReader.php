<?php

declare( strict_types=1 );

namespace Axpecto\Annotation;

use Axpecto\Collection\Klist;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionAttribute;
use ReflectionException;
use ReflectionParameter;

/**
 * Reads PHP8 attributes and turns them into AOP-style Annotation instances,
 * filtering by class vs. method targets and injecting their properties via DI.
 *
 * @template A of Annotation
 * @psalm-consistent-constructor
 */
class AnnotationReader {
	public function __construct(
		private readonly Container $container,
		private readonly ReflectionUtils $reflection
	) {
	}

	/**
	 * Fetch all annotations of a given type on a class.
	 *
	 * @template T
	 * @param class-string<T> $class
	 * @param class-string<A> $annotationClass
	 *
	 * @return Klist<T>
	 * @throws ReflectionException
	 */
	public function getClassAnnotations(
		string $class,
		string $annotationClass,
	): Klist {
		$raw = $this->reflection->getClassAttributes( $class );

		return $this
			->filterAndInject( $raw, $annotationClass )
			->map( fn( Annotation $ann ): Annotation => $ann->setAnnotatedClass( $class ) );
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
		$raw = $this->reflection->getMethodAttributes( $class, $method );

		return $this
			->filterAndInject( $raw, $annotationClass )
			->map( fn( Annotation $ann ): Annotation => $ann
				->setAnnotatedClass( $class )
				->setAnnotatedMethod( $method )
			);
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
		$classAnns  = $this->getClassAnnotations( $class, $annotationClass );
		$methodAnns = $this->reflection
			->getAnnotatedMethods( $class, $annotationClass )
			->map( fn( \ReflectionMethod $m ) => $this->getMethodAnnotations( $class, $m->getName(), $annotationClass )
			)
			->flatten();

		return $classAnns->merge( $methodAnns );
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
		$parameter = listFrom( $this->reflection->getClassMethod( $class, $method )->getParameters() )
			->filter( fn( ReflectionParameter $p ) => $p->getName() === $parameterName )
			->firstOrNull();

		if ( ! $parameter ) {
			return emptyList();
		}

		return listFrom( $parameter->getAttributes() )
			->map( fn( ReflectionAttribute $p ) => $p->newInstance() )
			->maybe( fn( Klist $attributes ) => $this->filterAndInject( $attributes, $annotationClass ) )
			->foreach( fn( Annotation $ann ) => $ann->setAnnotatedClass( $class )->setAnnotatedMethod( $method ) );
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
		$attributes = $this->reflection
			->getReflectionClass( $class )
			->getProperty( $property )
			->getAttributes();

		return listFrom( $attributes )
			->map( fn( ReflectionAttribute $a ) => $a->newInstance() )
			->maybe( fn( Klist $attributes ) => $this->filterAndInject( $attributes, $annotationClass ) )
			->firstOrNull()
			?->setAnnotatedClass( $class );
	}

	/**
	 * @template T of Annotation
	 * @param Klist<Annotation> $instances
	 * @param class-string<T> $annotationClass
	 *
	 * @return Klist<T>
	 */
	private function filterAndInject( Klist $instances, string $annotationClass ): Klist {
		return $instances
			->filter( fn( $i ) => is_a( $i, $annotationClass, true ) )
			->foreach( fn( Annotation $ann ) => $this->container->applyPropertyInjection( $ann ) );
	}
}

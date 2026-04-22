<?php
declare( strict_types=1 );

namespace Axpecto\Container;

use Axpecto\Annotation\AnnotationReader;
use Axpecto\Container\Annotation\DefaultImplementation;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;

/**
 * Knows how to ‘new Class(...args)’ and then property‑inject `@Inject`.
 */
class DependencyResolver {
	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly Container $container,
		private readonly AnnotationReader $annotationReader,
	) {
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function autowire( string $class ): object {
		// Apply constructor injection
		$args = $this->reflect
			->getConstructorArguments( $class )
			->map( fn( Argument $arg ) => $this->resolveArgument( $arg ) )
			->toArray();

		if ( ! class_exists( $class ) ) {
			$defaultImplementation = $this->annotationReader->getClassAnnotations( $class, DefaultImplementation::class )->firstOrNull();
			if ( $defaultImplementation === null ) {
				throw new Exception( "Class $class not found and no default implementation provided." );
			}

			return $this->autowire( $defaultImplementation->className );
		}

		return new $class( ...$args );
	}

	/**
	 * @template T
	 * @param object<T> $instance
	 *
	 * @return T
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function applyPropertyInjection( object $instance ): object {
		foreach ( $this->reflect->getAnnotatedProperties( $instance::class, Inject::class ) as $arg ) {
			$annotation = $this->annotationReader->getPropertyAnnotation( $instance::class, $arg->name, Inject::class );

			$value = $this->container->get( $annotation->class ?? $arg->type ?? $arg->name );

			$this->reflect->setPropertyValue( $instance, $arg->name, $value );
		}

		return $instance;
	}

	/**
	 * @throws Exception
	 */
	private function resolveArgument( Argument $arg ): mixed {
		$dependencyOrValue = in_array( $arg->type, [ 'string', 'int', 'bool' ] ) ? $arg->name : $arg->type;

		return $this->container->get( $dependencyOrValue );
	}
}

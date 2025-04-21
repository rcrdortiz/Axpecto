<?php

namespace Axpecto\Reflection;

use Attribute;
use Axpecto\Annotation\Annotation;
use Axpecto\Collection\Klist;
use Axpecto\Reflection\Dto\Argument;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * ReflectionUtils
 *
 * A utility class that provides helper methods for reflection-based operations,
 * including fetching annotations, constructor arguments, method definitions, etc.
 *
 * @template T
 */
class ReflectionUtils {

	/**
	 * @var array<string, ReflectionClass> Cached ReflectionClass instances.
	 */
	private array $reflectionClasses = [];

	/**
	 * Returns a ReflectionClass instance, using caching for optimization.
	 *
	 * @param class-string<T> $class
	 *
	 * @return ReflectionClass<T>
	 * @throws ReflectionException
	 */
	public function getReflectionClass( string $class ): ReflectionClass {
		return $this->reflectionClasses[ $class ] ??= new ReflectionClass( $class );
	}

	/**
	 * @return Klist<Attribute>
	 * @throws ReflectionException
	 */
	public function getClassAttributes( string $class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getAttributes() )
			->map( fn( ReflectionAttribute $attribute ) => $attribute->newInstance() );
	}

	/**
	 * Returns a list of attributes for a method.
	 *
	 * @return Klist<Attribute>
	 * @throws ReflectionException
	 */
	public function getMethodAttributes( string $class, string $method ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getMethod( $method )->getAttributes() )
			->map( fn( ReflectionAttribute $attribute ) => $attribute->newInstance() );
	}

	/**
	 * Fetches methods annotated with a specific annotation class.
	 *
	 * @param class-string<T> $class
	 * @param class-string<T> $with
	 *
	 * @return Klist<ReflectionMethod>
	 * @throws ReflectionException
	 */
	public function getAnnotatedMethods( string $class, string $with = Annotation::class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getMethods() )
			->filter( fn( ReflectionMethod $m ) => listFrom( $m->getAttributes() )
				->filter( fn( ReflectionAttribute $attribute ) => $attribute->getName() === $with )
				->isNotEmpty()
			);
	}

	/**
	 * Fetches all methods of a class.
	 *
	 * @param string $class
	 *
	 * @return Klist<ReflectionMethod>
	 * @throws ReflectionException
	 */
	public function getMethods( string $class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getMethods() )
			->filter( fn( ReflectionMethod $method ) => ! $method->isConstructor() && ! $method->isPrivate() && ! $method->isFinal() );
	}

	/**
	 * Fetches all abstract methods of a class.
	 *
	 * @param string $class
	 *
	 * @return Klist<ReflectionMethod>
	 * @throws ReflectionException
	 */
	public function getAbstractMethods( string $class ): Klist {
		return $this->getMethods( $class )
		            ->filter( fn( ReflectionMethod $method ) => $method->isAbstract() );
	}

	/**
	 * Fetches the constructor arguments of a class as Arguments.
	 *
	 * @param class-string<T> $class
	 *
	 * @return Klist<Argument>
	 * @throws ReflectionException
	 */
	public function getConstructorArguments( string $class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getConstructor()?->getParameters() ?? [] )
			->map( $this->reflectionToArgument( ... ) );
	}

	/**
	 * Fetches properties annotated with a specific annotation and returns an Argument list.
	 *
	 * @param string $class
	 * @param string $annotationClass
	 *
	 * @return Klist<Argument>
	 * @throws ReflectionException
	 */
	public function getAnnotatedProperties( string $class, string $annotationClass = Annotation::class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getProperties() )
			->filter( fn( ReflectionProperty $property ) => $this->filterAnnotatedProperties( $property, $annotationClass ) )
			->map( $this->reflectionToArgument( ... ) );
	}

	/**
	 * Sets the value of a property in an object instance.
	 *
	 * @param object $instance
	 * @param string $property
	 * @param mixed $value
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	public function setPropertyValue( object $instance, string $property, mixed $value ): void {
		$reflectionProperty = new ReflectionProperty( $instance, $property );
		/** @psalm-suppress UnusedMethodCall */
		$reflectionProperty->setAccessible( true );
		$reflectionProperty->setValue( $instance, $value );
	}

	/**
	 * Gets a map of method arguments and their values.
	 *
	 * @param string $class
	 * @param string $method
	 * @param array<ReflectionParameter> $arguments
	 *
	 * @return array
	 * @throws ReflectionException
	 */
	public function mapValuesToArguments( string $class, string $method, array $arguments ): array {
		$parameters = $this->getReflectionClass( $class )
		                   ->getMethod( $method )
		                   ->getParameters();

		if ( count( $parameters ) === 0 ) {
			return [];
		}

		$parameters = listFrom( $parameters )->map( fn( ReflectionParameter $parameter ) => $parameter->getName() );

		$arguments = array_pad( $arguments, count( $parameters ), null );

		return array_combine( $parameters->toArray(), $arguments );
	}

	/**
	 * Checks if the supplied class is an interface.
	 *
	 * @param $class
	 *
	 * @return bool
	 * @throws ReflectionException
	 */
	public function isInterface( $class ): bool {
		return $this->getReflectionClass( $class )->isInterface();
	}

	/**
	 * Converts a ReflectionProperty or ReflectionParameter into an Argument DTO.
	 *
	 * @param ReflectionProperty|ReflectionParameter $reflection
	 *
	 * @return Argument
	 *
	 * @TODO Fix multivalued argument type.
	 */
	private function reflectionToArgument( ReflectionProperty|ReflectionParameter $reflection ): Argument {
		$name     = $reflection->getName();
		$type     = $reflection->getType()?->getName() ?? 'mixed';
		$nullable = $reflection->getType()?->allowsNull() ?? false;
		$default  = null;

		if ( $reflection instanceof ReflectionParameter ) {
			if ( $reflection->isDefaultValueAvailable() ) {
				$default = $reflection->getDefaultValue();
			}
		} elseif ( $reflection instanceof ReflectionProperty ) {
			if ( $reflection->hasDefaultValue() ) {
				$default = $reflection->getDefaultValue();
			}
		}

		return new Argument(
			name: $name,
			type: $type,
			nullable: $nullable,
			default: $default
		);
	}

	/**
	 * Filters properties that are annotated with a specific annotation.
	 *
	 * @param ReflectionProperty $property
	 * @param string $annotationClass
	 *
	 * @return bool
	 */
	private function filterAnnotatedProperties( ReflectionProperty $property, string $annotationClass ): bool {
		return $this->getAnnotations(
			attributes: listFrom( $property->getAttributes() ),
			target: Attribute::TARGET_PROPERTY,
			annotationClass: $annotationClass
		)->isNotEmpty();
	}

	/**
	 * Fetches annotations based on the target.
	 *
	 * @param Klist<ReflectionAttribute> $attributes
	 * @param string|null $target
	 * @param string $annotationClass
	 *
	 * @return Klist<Annotation>
	 */
	private function getAnnotations( Klist $attributes, ?string $target, string $annotationClass ): Klist {
		return $attributes
			->filter( fn( ReflectionAttribute $attribute ) => $attribute->getTarget() == $target )
			->map( fn( ReflectionAttribute $attribute ) => class_exists( $attribute->getName() ) ? $attribute->newInstance() : null )
			->filter( fn( $annotation ) => $annotation instanceof $annotationClass );
	}

	/**
	 * @throws ReflectionException
	 */
	public function getClassMethod( string $class, string $method ): ReflectionMethod {
		return $this->getReflectionClass( $class )->getMethod( $method );
	}
}
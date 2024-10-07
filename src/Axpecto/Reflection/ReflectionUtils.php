<?php

namespace Axpecto\Reflection;

use Attribute;
use Axpecto\Aop\Annotation;
use Axpecto\Collection\Concrete\Klist;
use Axpecto\Collection\Concrete\Kmap;
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
	 * Generates a method definition string with visibility, name, arguments, and return type.
	 *
	 * @param ReflectionMethod $method
	 *
	 * @return string
	 * @throws ReflectionException
	 */
	public function getMethodDefinitionString( ReflectionMethod $method ): string {
		$visibility         = $method->isProtected() ? 'protected' : 'public';
		$argumentListString = listFrom( $method->getParameters() )
			->map( function ( ReflectionParameter $arg ) {
				$definition = ( $arg->hasType() ? $arg->getType() . ' ' : '' ) . ( $arg->isVariadic() ? '...' : '' ) . "\${$arg->getName()}";
				if ( $arg->isDefaultValueAvailable() ) {
					$definition .= " = " . var_export( $arg->getDefaultValue(), true );
				} elseif ( ! $arg->isVariadic() ) {
					$definition .= " = null";
				}

				return $definition;
			} )
			->join( ',' );

		$returnType = $method->hasReturnType() ? ': ' . $method->getReturnType() : '';

		return "$visibility function {$method->getName()}($argumentListString)$returnType";
	}

	/**
	 * Fetches methods annotated with a specific annotation class.
	 *
	 * @param class-string<T> $class
	 * @param string          $with
	 *
	 * @return Klist<ReflectionMethod>
	 * @throws ReflectionException
	 */
	public function getAnnotatedMethods( string $class, string $with = Annotation::class ): Klist {
		return $this->getMethods( $class )
			->filter( fn( ReflectionMethod $method ) => $this->methodHasAnnotations( $method, $with ) );
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
	 * Fetches annotations for a method.
	 *
	 * @param class-string<T> $class
	 * @param string          $method
	 * @param string          $annotationClass
	 *
	 * @return Klist<Annotation>
	 * @throws ReflectionException
	 */
	public function getMethodAnnotations( string $class, string $method, string $annotationClass ): Klist {
		return $this->getAnnotations(
			attributes:      listFrom( $this->getReflectionClass( $class )->getMethod( $method )->getAttributes() ),
			target:          Attribute::TARGET_METHOD,
			annotationClass: $annotationClass
		);
	}

	/**
	 * Fetches annotations for a class.
	 *
	 * @param class-string<T> $class
	 * @param string          $annotationClass
	 *
	 * @return Klist<Annotation>
	 * @throws ReflectionException
	 */
	public function getClassAnnotations( string $class, string $annotationClass = Annotation::class ): Klist {
		return $this->getAnnotations(
			attributes:      listFrom( $this->getReflectionClass( $class )->getAttributes() ),
			target:          Attribute::TARGET_CLASS,
			annotationClass: $annotationClass
		);
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
	 * @param mixed  $value
	 *
	 * @return object The modified instance.
	 * @throws ReflectionException
	 */
	public function setPropertyValue( object $instance, string $property, mixed $value ): object {
		$reflectionProperty = new ReflectionProperty( $instance, $property );
		$reflectionProperty->setAccessible( true );
		$reflectionProperty->setValue( $instance, $value );

		return $instance;
	}

	/**
	 * Gets a map of method arguments and their values.
	 *
	 * @param string                     $class
	 * @param string                     $method
	 * @param array<ReflectionParameter> $arguments
	 *
	 * @return array
	 * @throws ReflectionException
	 */
	public function getMethodArguments( string $class, string $method, array $arguments ) {
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
	 * Gets default argument values for a method.
	 *
	 * @param string $class
	 * @param string $method
	 *
	 * @return Kmap<string, mixed>
	 * @throws ReflectionException
	 */
	public function getMethodArgumentsDefaults( string $class, string $method ): Kmap {
		$parameters = $this->getReflectionClass( $class )->getMethod( $method )->getParameters();
		if ( count( $parameters ) === 0 ) {
			return emptyMap();
		}

		return listFrom( $parameters )
			->mapOf( fn( ReflectionParameter $value ) => [ $value->getName() => $value->isDefaultValueAvailable() ? $value->getDefaultValue() : null, ] )
			->filterNotNull();
	}

	/**
	 * Fetches an annotation on a property.
	 *
	 * @param string $class
	 * @param string $property
	 * @param string $with
	 *
	 * @return Annotation|null
	 * @throws ReflectionException
	 */
	public function getPropertyAnnotated( string $class, string $property, string $with = Annotation::class ): ?Annotation {
		return listFrom( $this->getReflectionClass( $class )->getProperty( $property )->getAttributes() )
			->filter( fn( ReflectionAttribute $attribute ) => $attribute->getName() === $with )
			->firstOrNull()?->newInstance();
	}

	/**
	 * Gets the return type of a method.
	 *
	 * @param ReflectionMethod $method
	 *
	 * @return string|null
	 */
	public function getReturnType( ReflectionMethod $method ): ?string {
		return $method->hasReturnType() ? $method->getReturnType()->getName() : null;
	}

	/**
	 * Converts a ReflectionProperty or ReflectionParameter into an Argument DTO.
	 *
	 * @param ReflectionProperty|ReflectionParameter $property
	 *
	 * @return Argument
	 */
	private function reflectionToArgument( ReflectionProperty|ReflectionParameter $property ): Argument {
		return new Argument(
			name: $property->getName(),
			type: $property->getType()?->getName() ?? 'mixed',
		);
	}

	/**
	 * Filters properties that are annotated with a specific annotation.
	 *
	 * @param ReflectionProperty $property
	 * @param string             $annotationClass
	 *
	 * @return bool
	 */
	private function filterAnnotatedProperties( ReflectionProperty $property, string $annotationClass ): bool {
		return $this->getAnnotations(
			attributes:      listFrom( $property->getAttributes() ),
			target:          Attribute::TARGET_PROPERTY,
			annotationClass: $annotationClass
		)->isNotEmpty();
	}

	/**
	 * Checks if a method is annotated with a specific attribute.
	 *
	 * @param ReflectionMethod $method
	 * @param string           $annotationClass
	 *
	 * @return bool
	 */
	private function methodHasAnnotations( ReflectionMethod $method, string $annotationClass = Annotation::class ): bool {
		return $this->getAnnotations(
			attributes:      listFrom( $method->getAttributes() ),
			target:          Attribute::TARGET_METHOD,
			annotationClass: $annotationClass,
		)->isNotEmpty();
	}

	/**
	 * Checks if a method is overrideable when extending the base class.
	 *
	 * @param ReflectionMethod $method
	 *
	 * @return bool
	 */
	private function methodIsOverrideable( ReflectionMethod $method ): bool {
		return ! ( $method->isConstructor() || $method->isPrivate() || $method->isFinal() );
	}

	/**
	 * Fetches annotations based on the target.
	 *
	 * @param Klist<ReflectionAttribute> $attributes
	 * @param string|null                $target
	 * @param string                     $annotationClass
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
}
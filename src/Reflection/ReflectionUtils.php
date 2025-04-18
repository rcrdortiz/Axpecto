<?php

namespace Axpecto\Reflection;

use Attribute;
use Axpecto\Annotation\Annotation;
use Axpecto\Collection\Klist;
use Axpecto\Collection\Kmap;
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
	 * @param string $class
	 * @param string $method
	 *
	 * @return string
	 * @throws ReflectionException
	 */
	public function getMethodDefinitionString( string $class, string $method ): string {
		$reflectionMethod = $this->getReflectionClass( $class )->getMethod( $method );

		$visibility         = $reflectionMethod->isProtected() ? 'protected' : 'public';
		$argumentListString = listFrom( $reflectionMethod->getParameters() )
			->map( function ( ReflectionParameter $arg ) {
				$definition = ( $arg->hasType() ? $arg->getType() . ' ' : '' ) . ( $arg->isVariadic() ? '...' : '' ) . "\${$arg->getName()}";
				if ( $arg->isDefaultValueAvailable() ) {
					$definition .= " = " . var_export( $arg->getDefaultValue(), true );
				}

				return $definition;
			} )
			->join( ',' );

		$returnType = $reflectionMethod->hasReturnType() ? ': ' . $reflectionMethod->getReturnType() : '';

		return "$visibility function {$reflectionMethod->getName()}($argumentListString)$returnType";
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
	public function getMethodAnnotations( string $class, string $method, string $annotationClass = Annotation::class ): Klist {
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
			attributes:      $this->getAttributes( $class ),
			target:          Attribute::TARGET_CLASS,
			annotationClass: $annotationClass
		);
	}

	/**
	 * Fetches annotations for a parameter.
	 *
	 * @param class-string $class           The class name.
	 * @param string       $method          The method name (use "__construct" for constructor).
	 * @param string       $parameterName   The parameter name.
	 * @param string       $annotationClass The annotation class to filter by.
	 *
	 * @return Klist<Annotation>
	 * @throws ReflectionException
	 */
	public function getParamAnnotations(
		string $class,
		string $method,
		string $parameterName,
		string $annotationClass = Annotation::class
	): Klist {
		// Get the reflection of the specified method.
		$reflectionMethod = $this->getReflectionClass( $class )->getMethod( $method );

		// Find the parameter by name.
		$reflectionParameter = null;
		foreach ( $reflectionMethod->getParameters() as $parameter ) {
			if ( $parameter->getName() === $parameterName ) {
				$reflectionParameter = $parameter;
				break;
			}
		}

		if ( $reflectionParameter === null ) {
			throw new ReflectionException( "Parameter {$parameterName} not found in method {$method} of class {$class}" );
		}

		// Get attributes from the parameter.
		$attributes = listFrom( $reflectionParameter->getAttributes() );

		// Filter the annotations by target.
		// For parameters, the target is Attribute::TARGET_PARAMETER.
		return $this->getAnnotations(
			attributes:      $attributes,
			target:          Attribute::TARGET_PARAMETER,
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
	public function mapValuesToArguments( string $class, string $method, array $arguments ) {
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
	 * @param string $class
	 * @param string $method
	 *
	 * @return string|null
	 * @throws ReflectionException
	 */
	public function getReturnType( string $class, string $method ): ?string {
		$method = $this->getReflectionClass( $class )->getMethod( $method );

		return $method->hasReturnType() ? $method->getReturnType()->getName() : null;
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
	 * @return Klist<Attribute>
	 * @throws ReflectionException
	 */
	public function getClassAttributes( string $class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getAttributes() )
			->filter( fn( ReflectionAttribute $attribute ) => class_exists( $attribute->getName() ) )
			->map( fn( ReflectionAttribute $attribute ) => $attribute->newInstance() );
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
		$default = null;

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
			name:    $name,
			type:    $type,
			nullable: $nullable,
			default: $default
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
	private function getReflectionClass( string $class ): ReflectionClass {
		return $this->reflectionClasses[ $class ] ??= new ReflectionClass( $class );
	}

	/**
	 * Wrapper for getting attributes from a class as a Klist.
	 *
	 * @param string $class
	 *
	 * @return Klist
	 * @throws ReflectionException
	 */
	private function getAttributes( string $class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getAttributes() );
	}
}
<?php

namespace Axpecto\Reflection;

use Attribute;
use Axpecto\Aop\Annotation;
use Axpecto\Collection\Concrete\Klist;
use Axpecto\Reflection\Dto\Argument;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * @template T
 */
class ReflectionUtils {
	private mixed $reflectionClasses;

	public function getMethodDefinitionString( ReflectionMethod $method ): string {
		$visibility = $method->isProtected() ? 'protected' : 'public';

		$argumentListString = listFrom( $method->getParameters() )
			->map( function ( ReflectionParameter $arg ) {
				$definition = $arg->getType() . ' ' . ( $arg->isVariadic() ? '...' : '' ) . "\${$arg->getName()}";

				$definition .= match ( true ) {
					$arg->isDefaultValueAvailable() => " = {$arg->getDefaultValue()}",
					! $arg->isVariadic() && ! $arg->isDefaultValueAvailable() => ' = null',
					default => '',
				};

				return $definition;
			} )
			->join( separator: ',' );

		return "$visibility function {$method->getName()}($argumentListString)"
		       . ( $method->hasReturnType() ? ": {$method->getReturnType()}" : '' );
	}

	/**
	 * @param class-string<T> $class
	 *
	 * @return Klist<ReflectionMethod>
	 * @throws ReflectionException
	 */
	public function getAnnotatedMethods( string $class, string $with = Annotation::class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getMethods() )
			->filter( fn( ReflectionMethod $method ) => $this->methodHasAnnotations( $method, annotationClass: $with ) )
			->filter( $this->methodIsOverrideable( ... ) );
	}

	/**
	 * @param class-string<T> $class
	 * @param string          $method
	 * @param string          $annotationClass
	 *
	 * @return Klist<Annotation>
	 * @throws ReflectionException
	 */
	public function getMethodAnnotations( string $class, string $method, string $annotationClass ): Klist {
		$attributes = $this->getReflectionClass( $class )
		                   ->getMethod( $method )
		                   ->getAttributes();

		return $this->getAnnotations( listFrom( $attributes ), Attribute::TARGET_METHOD, $annotationClass );
	}

	/**
	 * @param class-string<T> $class
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
	 * @param class-string<T> $class
	 *
	 * @return Klist<Argument>
	 * @throws ReflectionException
	 */
	public function getConstructorArguments( string $class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getConstructor()?->getParameters() ?? [] )
			->map( $this->reflectionToArgument( ... ) );
	}

	public function getAnnotatedProperties( string $class, string $annotationClass = Annotation::class ): Klist {
		return listFrom( $this->getReflectionClass( $class )->getProperties() )
			->filter( fn( ReflectionProperty $property ) => $this->filterAnnotatedProperties( $property, $annotationClass ) )
			->map( $this->reflectionToArgument( ... ) );
	}

	public function setPropertyValue( object $instance, string $property, $value ) {
		$reflectionProperty = new ReflectionProperty( $instance, $property );
		$reflectionProperty->setAccessible( true );
		$reflectionProperty->setValue( $instance, $value );

		return $instance;
	}

	private function reflectionToArgument( ReflectionProperty|ReflectionParameter $property ): Argument {
		return new Argument(
			name: $property->getName(),
			type: $property->getType()->getName()
		);
	}

	private function filterAnnotatedProperties( ReflectionProperty $property, string $annotationClass ): bool {
		return $this->getAnnotations(
			attributes:      listFrom( $property->getAttributes() ),
			target:          Attribute::TARGET_PROPERTY,
			annotationClass: $annotationClass
		)->isNotEmpty();
	}

	private function methodHasAnnotations( ReflectionMethod $method, string $annotationClass = Annotation::class ): bool {
		return $this->getAnnotations(
			attributes:      listFrom( $method->getAttributes() ),
			target:          Attribute::TARGET_METHOD,
			annotationClass: $annotationClass,
		)->isNotEmpty();
	}

	private function methodIsOverrideable( ReflectionMethod $method ): bool {
		return ! ( $method->isConstructor() || $method->isPrivate() || $method->isFinal() );
	}

	/**
	 * @param Klist<ReflectionAttribute> $attributes
	 * @param string|null                $target
	 *
	 * @return Klist<Annotation>
	 */
	private function getAnnotations( KList $attributes, string $target = null, string $annotationClass = Annotation::class ): Klist {
		return $attributes
			->filter( fn( ReflectionAttribute $attribute ) => $attribute->getTarget() == $target )
			->map( fn( ReflectionAttribute $attribute ) => $attribute->newInstance() )
			->filter( fn( $annotation ) => $annotation instanceof $annotationClass );
	}

	/**
	 * @param class-string<T> $class
	 *
	 * @return ReflectionClass<T>
	 * @throws ReflectionException
	 */
	private function getReflectionClass( string $class ): ReflectionClass {
		return $this->reflectionClasses[ $class ] = $this->reflectionClasses[ $class ] ?? new ReflectionClass( $class );
	}
}
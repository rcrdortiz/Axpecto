<?php

namespace Axpecto\Container;

use Axpecto\Aop\Annotation;
use Axpecto\Aop\InterceptedClassFactory;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Container\Annotation\Singleton;
use Axpecto\Container\Dto\ContainerValue;
use Axpecto\Container\Exception\CircularReferenceException;
use Axpecto\Container\Exception\ClassNotFoundException;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;

/**
 * @template T
 */
#[Singleton]
final class Container {

	private InterceptedClassFactory $interceptedClassFactory;
	private ReflectionUtils $reflect;

	public function __construct(
		private array $values = [],
		private array $services = [],
		private array $implementations = [],
		private array $autoWiring = [],
	) {
		// @TODO Fix this mess, this is just a workaround to make the Interceptor work.
		$this->implementations[ self::class ] = $this;
		$this->reflect                        = new ReflectionUtils();
		$this->interceptedClassFactory        = new InterceptedClassFactory( $this->reflect, $this );
	}

	/**
	 * @throws ReflectionException
	 */
	public function addClassInstance( string $class, object $instance ): void {
		$isSingleton = ! ! $this->reflect
			->getClassAnnotations( $class )
			->filter( fn( Annotation $annotation ) => $annotation instanceof Singleton )
			->firstOrNull();

		if ( $isSingleton ) {
			$this->implementations[ $class ] = $instance;
		}

		$this->addImplementation( $class, $class );
	}

	public function addValue( string $name, string $value ) {
		$this->values[ $name ] = $value;
	}

	public function addImplementation( string $name, string $class ) {
		$this->services[ $name ] = $class;
	}

	/**
	 * @param class-string<T> $name
	 *
	 * @return T
	 * @throws Exception
	 */
	public function get( string $name ) {
		$value = $this->findValueForName( $name );

		return match ( $value ) {
			ContainerValue::NoValue => $this->autoWire( $name ),
			default => $value,
		};
	}

	/**
	 * @param string $name
	 *
	 * @return T
	 * @throws Exception
	 */
	private function autoWire( string $name ) {
		$class = $this->getClassForName( $name );
		$this->checkCircularReference( $class );
		$this->addAutoWiring( $class );

		if ( ! class_exists( $class ) ) {
			throw new ClassNotFoundException( $class, $this->autoWiring );
		}

		$instance = new $class( ...$this->autoWireConstructorArguments( $class ) );
		$instance = $this->applyPropertyInjection( $instance );
		$this->addClassInstance( $class, $instance );
		$this->removeAutoWiring( $class );

		return $instance;
	}

	private function applyPropertyInjection( object $instance ) {
		return $this->reflect
			       ->getAnnotatedProperties( $instance::class, Inject::class )
			       ->toMap( fn( Argument $arg ) => [
				       $arg->name,
				       $this->get( $arg->type ?? $arg->name ),
			       ] )
			       ->map( fn( $key, $value ) => $this->reflect->setPropertyValue( $instance, $key, $value ) )
			       ->firstOrNull() ?? $instance;
	}

	/**
	 * @param class-string<T> $class
	 *
	 * @return T
	 * @throws Exception
	 */
	private function autoWireConstructorArguments( string $class ) {
		return $this->reflect->getConstructorArguments( $class )
		                     ->map( fn( Argument $arg ) => $this->get( $arg->type ?? $arg->name ) );
	}

	/**
	 * @throws Exception
	 */
	private function checkCircularReference( string $name ): void {
		( $this->autoWiring[ $name ] ?? false ) && throw new CircularReferenceException( $name );
	}

	private function findValueForName( string $name ) {
		$name = $this->services[ $name ] ?? $name;
		return $this->implementations[ $name ] ?? $this->values[ $name ] ?? ContainerValue::NoValue;
	}

	private function getClassForName( string $name ) {
		$name = $this->services[ $name ] ?? $name;

		if ( ! class_exists( $name ) ) {
			return $name;
		}

		return $this->interceptedClassFactory->getClassname( $name );
	}

	private function addAutoWiring( string $class ): void {
		$this->autoWiring[ $class ] = $class;
	}

	private function removeAutoWiring( mixed $class ): void {
		unset( $this->autoWiring[ $class ] );
	}
}

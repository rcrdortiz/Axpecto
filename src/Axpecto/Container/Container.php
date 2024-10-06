<?php

namespace Axpecto\Container;

use Axpecto\Aop\ClassBuilder;
use Axpecto\Aop\Exception\ClassAlreadyBuiltException;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Container\Annotation\Singleton;
use Axpecto\Container\Exception\AutowireDependencyException;
use Axpecto\Container\Exception\CircularReferenceException;
use Axpecto\Container\Exception\UnresolvedDependencyException;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;

/**
 * Class Container
 *
 * This class is responsible for dependency injection and handling object instantiation and autowiring.
 * It manages class instances, services, and values, and provides methods to autowire dependencies,
 * handle property injections, and avoid circular references.
 *
 * @template T
 */
#[Singleton]
final class Container {

	private ClassBuilder $classBuilder;
	private ReflectionUtils $reflect;

	public function __construct(
		private array $values = [],
		private array $bindings = [],
		private array $instances = [],
		private array $autoWiring = [],
	) {
		$this->reflect                             = new ReflectionUtils();
		$this->instances[ ReflectionUtils::class ] = $this->reflect;
		$this->classBuilder                        = new ClassBuilder( $this->reflect, $this );
		$this->instances[ ClassBuilder::class ]    = $this->classBuilder;
		$this->instances[ self::class ]            = $this;
	}

	/**
	 * @throws ReflectionException
	 */
	public function addClassInstance( string $class, object $instance ): void {
		$this->instances[ $class ] = $instance;
	}

	public function addValue( string $name, string $value ): void {
		$this->values[ $this->getValueKey( $name ) ] = $value;
	}

	public function bind( string $classOrInterface, string $class ): void {
		$this->bindings[ $classOrInterface ] = $class;
	}

	/**
	 * @param string $dependencyName
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get( string $dependencyName ): mixed {
		try {
			$dependency = $this->seekDependency( $dependencyName );
		} catch ( UnresolvedDependencyException ) {
			$class      = $this->buildClass( $dependencyName );
			$dependency = $this->autoWire( $class );
		}

		return $dependency;
	}

	/**
	 * @param string $dependency
	 *
	 * @return T
	 * @throws Exception
	 */
	private function autoWire( string $class ) {
		$this->checkCircularReference( $class );
		$this->addAutoWiring( $class );
		$instance = new $class( ...$this->autoWireConstructorArguments( $class ) );
		$instance = $this->applyPropertyInjection( $instance );
		$this->addClassInstance( $class, $instance );
		$this->removeAutoWiring( $class );

		return $instance;
	}

	private function applyPropertyInjection( object $instance ) {
		$inject = $this->reflect->getAnnotatedProperties( $instance::class, Inject::class )->firstOrNull();

		if ( ! $inject ) {
			return $instance;
		}

		$provided = null;
		/** @var Inject $annotation */
		$annotation = $this->reflect->getPropertyAnnotated( get_class( $instance ), $inject->name, with: Inject::class );
		if ( $annotation->args ) {
			$provided = new ( $inject->type )( ...$annotation->args );
		}

		return $this->reflect
			       ->getAnnotatedProperties( $instance::class, Inject::class )
			       ->mapOf( fn( Argument $arg ) => [ $arg->name => $provided ?? $this->getFromArgument( $arg ) ] )
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
		return $this->reflect
			->getConstructorArguments( $class )
			->map( $this->getFromArgument( ... ) );
	}

	/**
	 * @throws Exception
	 */
	private function getFromArgument( Argument $arg ) {
		$dependencyName = $arg->type;
		if ( in_array( $arg->type, [ 'string', 'int', 'bool' ] ) ) {
			$dependencyName = $arg->name;
		}

		return $this->get( $dependencyName );
	}

	/**
	 * @throws Exception
	 */
	private function checkCircularReference( string $name ): void {
		if ( isset( $this->autoWiring[ $name ] ) ) {
			throw new CircularReferenceException( $name );
		}
	}

	private function seekDependency( string $dependencyName ) {
		if ( isset( $this->bindings[ $dependencyName ] ) ) {
			$dependencyName = $this->bindings[ $dependencyName ];
		}

		if ( isset( $this->instances[ $dependencyName ] ) ) {
			return $this->instances[ $dependencyName ];
		}

		$valueKey = $this->getValueKey( $dependencyName );
		if ( isset( $this->values[ $valueKey ] ) ) {
			return $this->values[ $valueKey ];
		}

		throw new UnresolvedDependencyException( $dependencyName );
	}

	private function addAutoWiring( string $class ): void {
		$this->autoWiring[ $class ] = $class;
	}

	private function removeAutoWiring( mixed $class ): void {
		unset( $this->autoWiring[ $class ] );
	}

	private function getValueKey( string $name ): string {
		return "container.value.$name";
	}

	private function buildClass( mixed $dependency ) {
		try {
			$class = $this->classBuilder->build( $dependency );
			$this->bind( $dependency, $class );

			return $class;
		} catch ( ClassAlreadyBuiltException ) {
			return $this->bindings[ $dependency ];
		} catch ( ReflectionException $exception ) {
			throw new AutowireDependencyException( end( $this->autoWiring ), $dependency, $exception );
		}
	}
}

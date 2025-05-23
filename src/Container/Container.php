<?php

namespace Axpecto\Container;

use Axpecto\Annotation\AnnotationReader;
use Axpecto\ClassBuilder\ClassBuilder;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Container\Exception\AutowireDependencyException;
use Axpecto\Container\Exception\CircularReferenceException;
use Axpecto\Container\Exception\ClassAlreadyBuiltException;
use Axpecto\Container\Exception\UnresolvedDependencyException;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;
use RuntimeException;

/**
 * Class Container
 *
 * A Dependency Injection Container responsible for managing object instantiation, autowiring,
 * and handling circular references. It allows binding interfaces to implementations,
 * and injecting dependencies via annotations.
 *
 * @template T
 */
class Container {

	/**
	 * Responsible for creating proxy and intercepted classes.
	 *
	 * @var ClassBuilder The class builder instance.
	 */
	private ClassBuilder $classBuilder;

	/**
	 * Reflection utility for class analysis.
	 *
	 * @var ReflectionUtils The reflection utility instance.
	 */
	private ReflectionUtils $reflect;

	/**
	 * Reads annotations and injects dependencies.
	 *
	 * @var AnnotationReader The annotation reader instance.
	 */
	private AnnotationReader $annotationReader;

	/**
	 * Container constructor.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param array $values Stores constant values (like configs).
	 * @param array $bindings Maps interfaces or abstract classes to concrete implementations.
	 * @param array $instances Stores class instances (usually singletons).
	 * @param array $autoWiring Tracks classes currently being autowired to prevent circular references.
	 */
	public function __construct(
		private array $values = [],
		private array $bindings = [],
		private array $instances = [],
		private array $autoWiring = [],
	) {
		$this->reflect                             = new ReflectionUtils();
		$this->instances[ ReflectionUtils::class ] = $this->reflect;

		$this->annotationReader                     = new AnnotationReader(
			container: $this,
			reflection: $this->reflect,
		);
		$this->instances[ AnnotationReader::class ] = $this->annotationReader;

		$this->classBuilder                     = new ClassBuilder(
			reflect: $this->reflect,
			reader: $this->annotationReader,
		);
		$this->instances[ ClassBuilder::class ] = $this->classBuilder;
		$this->instances[ self::class ]         = $this;
	}

	/**
	 * Adds a class instance to the container.
	 *
	 * @param string $class The class name.
	 * @param object $instance The instance of the class.
	 */
	public function addClassInstance( string $class, object $instance ): void {
		$this->instances[ $class ] = $instance;
	}

	/**
	 * Adds a value (e.g., config or constant) to the container.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param string $name The name of the value.
	 * @param mixed $value The value to add.
	 */
	public function addValue( string $name, mixed $value ): void {
		$this->values[ $this->getValueKey( $name ) ] = $value;
	}

	/**
	 * Binds an interface or class to a specific implementation.
	 *
	 * @param string $classOrInterface The class or interface name.
	 * @param string $class The class name to bind.
	 */
	public function bind( string $classOrInterface, string $class ): void {
		$this->bindings[ $classOrInterface ] = $class;
	}

	/**
	 * Retrieves a dependency from the container.
	 *
	 * @param string<T> | string $dependencyName The name of the dependency.
	 *
	 * @return T| mixed The resolved dependency.
	 * @throws Exception If the dependency cannot be resolved.
	 */
	public function get( string $dependencyName ): mixed {
		try {
			return $this->seekDependency( $dependencyName );
		} catch ( UnresolvedDependencyException ) {
			// If not found, attempt autowiring
			$class = $this->buildClass( $dependencyName );

			return $this->autoWire( $class );
		}
	}

	/**
	 * Autowires the class, injecting its dependencies.
	 *
	 * @param string $class The class to autowire.
	 *
	 * @return object The autowired instance.
	 * @throws Exception If autowiring fails or circular references are detected.
	 */
	private function autoWire( string $class ): object {
		$this->checkCircularReference( $class );
		$this->addAutoWiring( $class );

		// Instantiate and inject dependencies
		$instance = new $class( ...$this->autoWireConstructorArguments( $class ) );
		$this->applyPropertyInjection( $instance );

		$this->addClassInstance( $class, $instance );
		$this->removeAutoWiring( $class );

		return $instance;
	}

	/**
	 * Applies property injection to an instance based on the Inject annotation.
	 *
	 * @param object $instance The instance to inject.
	 *
	 * @throws Exception If the dependency cannot be resolved.
	 */
	public function applyPropertyInjection( object $instance ): void {
		$propertiesToInject = $this->reflect->getAnnotatedProperties( $instance::class, Inject::class );

		foreach ( $propertiesToInject as $property ) {
			/** @var Inject $annotation */
			$annotation = $this->annotationReader->getPropertyAnnotation( $instance::class, $property->name, Inject::class );

			if ( ! empty( $annotation->args ) ) {
				$type = $property->type;

				if ( ! is_string( $type ) || ! class_exists( $type ) ) {
					throw new RuntimeException( "Cannot instantiate property {$property->name}: missing or invalid type." );
				}

				$value = new $type( ...$annotation->args );
			} elseif ( ! empty( $annotation->class ) ) {
				$value = $this->get( $annotation->class );
			} else {
				$value = $this->get( $property->type );
			}

			// Set the property value
			$this->reflect->setPropertyValue( $instance, $property->name, $value );
		}
	}

	/**
	 * Resolves constructor arguments via autowiring.
	 *
	 * @param string $class The class name.
	 *
	 * @return array The resolved constructor arguments.
	 * @throws Exception If the dependencies cannot be resolved.
	 */
	private function autoWireConstructorArguments( string $class ): array {
		return $this->reflect
			->getConstructorArguments( $class )
			->map( fn( Argument $arg ) => $this->getFromArgument( $arg ) )
			->toArray();
	}

	/**
	 * Resolves the value or service for the given argument.
	 *
	 * @param Argument $arg The argument to resolve.
	 *
	 * @return mixed The resolved value or dependency.
	 * @throws Exception If the dependency cannot be resolved.
	 */
	private function getFromArgument( Argument $arg ): mixed {
		return $this->get( in_array( $arg->type, [ 'string', 'int', 'bool' ] ) ? $arg->name : $arg->type );
	}

	/**
	 * Checks if there is a circular reference during autowiring.
	 *
	 * @param string $class The class name.
	 *
	 * @throws CircularReferenceException If a circular reference is detected.
	 */
	private function checkCircularReference( string $class ): void {
		if ( isset( $this->autoWiring[ $class ] ) ) {
			throw new CircularReferenceException( $class );
		}
	}

	/**
	 * Seeks and returns the dependency from bindings, instances, or values.
	 *
	 * @param string $dependencyName The name of the dependency.
	 *
	 * @return mixed The resolved dependency.
	 * @throws UnresolvedDependencyException If the dependency cannot be resolved.
	 */
	private function seekDependency( string $dependencyName ): mixed {
		$dependencyName = $this->bindings[ $dependencyName ] ?? $dependencyName;

		return $this->instances[ $dependencyName ]
		       ?? $this->values[ $this->getValueKey( $dependencyName ) ]
		          ?? throw new UnresolvedDependencyException( $dependencyName );
	}

	/**
	 * Builds a class and binds it to the container.
	 *
	 * @param string $dependency The dependency name.
	 *
	 * @return string The built class name.
	 * @throws AutowireDependencyException If the class cannot be built.
	 */
	private function buildClass( string $dependency ): string {
		$dependency = $this->bindings[ $dependency ] ?? $dependency;
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

	/**
	 * Adds a class to the auto-wiring tracking to detect circular references.
	 *
	 * @param string $class The class name.
	 */
	private function addAutoWiring( string $class ): void {
		$this->autoWiring[ $class ] = $class;
	}

	/**
	 * Removes a class from the auto-wiring tracking.
	 *
	 * @param string $class The class name.
	 */
	private function removeAutoWiring( string $class ): void {
		unset( $this->autoWiring[ $class ] );
	}

	/**
	 * Generates a value key for internal value storage.
	 *
	 * @param string $name The base name.
	 *
	 * @return string The generated value key.
	 */
	private function getValueKey( string $name ): string {
		return "container.value.$name";
	}
}

<?php
declare( strict_types=1 );

namespace Axpecto\Container;

use Axpecto\Annotation\AnnotationReader;
use Axpecto\Annotation\AnnotationService;
use Axpecto\ClassBuilder\ClassBuilder;
use Axpecto\Collection\Kmap;
use Axpecto\Container\Exception\AutowireDependencyException;
use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;

class Container {
	private CircularReferenceGuard $guard;
	private DependencyResolver $resolver;
	private ClassBuilder $builder;

	/**
	 * @throws Exception
	 */
	public function __construct(
		private readonly Kmap $singletons = new Kmap( mutable: true ),
		private readonly Kmap $values = new Kmap( mutable: true ),
		private readonly Kmap $bindings = new Kmap( mutable: true ),
	) {
		$this->guard       = new CircularReferenceGuard;
		$reflect           = new ReflectionUtils;
		$reader            = new AnnotationReader( $reflect );
		$this->resolver    = new DependencyResolver( $reflect, $this, $reader );
		$annotationService = new AnnotationService( $reader, $this->resolver );
		$this->builder     = new ClassBuilder( $reflect, $annotationService );

		// preset self
		$this->singletons->add( self::class, $this );
	}

	/**
	 * @throws Exception
	 */
	public function addValue( string $key, mixed $value ): void {
		$this->values->add( $key, $value );
	}

	/**
	 * @throws Exception
	 */
	public function addClassInstance( string $class, object $instance ): void {
		$this->singletons->add( $class, $instance );
	}

	/**
	 * @throws Exception
	 */
	public function bind( string $abstract, string $concrete ): void {
		$this->bindings->add( $abstract, $concrete );
	}

	/**
	 * @template T
	 * @throws Exception
	 * @param class-string<T> $id
	 * @return T|mixed
	 */
	public function get( string $id ): mixed {
		// Do we have a singleton for it
		if ( isset( $this->singletons[ $id ] ) ) {
			return $this->singletons[ $id ];
		}

		// Do we hava a value stored for it
		if ( isset( $this->values[ $id ] ) ) {
			return $this->values[ $id ];
		}

		// Check for a concrete binding or fallback to the id
		$type = $this->bindings[ $id ] ?? $id;

		// Build class
		$className = $this->builder->build( $type );

		// Autowire the class
		try {
			$this->guard->enter( $className );
			$instance = $this->resolver->autowire( $className );
			$this->resolver->applyPropertyInjection( $instance );
			$this->singletons->add( $id, $instance );
			$this->guard->leave( $className );

			return $instance;
		} catch ( ReflectionException $e ) {
			// Map a generic ReflectionException to a more specific one.
			throw new AutowireDependencyException( $className, $type, $e );
		}
	}
}

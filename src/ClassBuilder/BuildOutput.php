<?php

namespace Axpecto\ClassBuilder;

use Axpecto\Annotation\Annotation;
use Axpecto\Collection\Klist;
use Axpecto\Collection\Kmap;
use Axpecto\Container\Annotation\Inject;
use Exception;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Class BuildOutput
 *
 * This class encapsulates the output of a build process for handling methods and properties
 * in dynamically generated code. It aggregates two mutable data structures (MutableKmap).
 * Methods directly modify the internal state, reflecting a mutable design.
 *
 * - The `addMethod()` method adds a new method with its signature and implementation to the output.
 * - The `addProperty()` method adds a new property to the output.
 * - The `add()` method appends additional methods and properties to the output.
 * - The `isEmpty()` method checks if any output (methods or properties) exists.
 *
 * @package Axpecto\Aop\Build
 */
class BuildOutput {

	/**
	 * Constructor for the BuildOutput class.
	 **
	 *
	 * @param Kmap $methods List of methods in the output.
	 * @param Kmap $properties List of class properties in the output.
	 */
	public function __construct(
		public readonly string $class,
		public readonly Kmap $methods = new Kmap( mutable: true ),
		public readonly Kmap $properties = new Kmap( mutable: true ),
		public readonly Kmap $traits = new Kmap( mutable: true ),
		// @TODO I might change this.
		private array $methodAnnotations = [],
	) {
	}

	/**
	 * Add a method with its signature and implementation to the output.
	 * Modifies the internal state directly.
	 *
	 * @param string $name The method name.
	 * @param string $signature The method signature.
	 * @param string $implementation The method implementation.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function addMethod( string $name, string $signature, string $implementation ): void {
		$this->methods->add( $name, "$signature {\n\t\t$implementation\n\t}\n" );
	}

	/**
	 * @template T of Annotation
	 * @param string $methodName
	 * @param class-string<T> $annotation
	 *
	 * @throws Exception
	 */
	public function annotateMethod( string $methodName, string $annotation ): void {
		$this->methodAnnotations[$methodName][] = '#[' . $annotation . ']';
	}

	public function getMethodAnnotations( string $methodName ): Klist {
		return listFrom( $this->methodAnnotations[$methodName] ?? [] );
	}

	/**
	 * Add a property to the output.
	 * Modifies the internal state directly.
	 *
	 * @param string $name The property name.
	 * @param string $implementation The property implementation.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function addProperty( string $name, string $implementation ): void {
		$this->properties->add( $name, $implementation );
	}

	/**
	 * Inject a property into the output.
	 *
	 * @psalm-suppress PossiblyUnusedReturnValue
	 *
	 * @param string $name
	 * @param string $class
	 *
	 * @return string Reference to variable.
	 * @throws Exception
	 */
	public function injectProperty( string $name, string $class ): string {
		$this->addProperty(
			name: $class,
			implementation: "#[" . Inject::class . "] protected $class \$$name;",
		);

		return $name;
	}

	/**
	 * Append additional methods and properties to the current output.
	 * Modifies the internal state directly by merging.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param Kmap $methods List of methods to append.
	 * @param Kmap $properties List of properties to append.
	 *
	 * //     * @return void
	 */
	public function add( Kmap $methods, Kmap $properties ): void {
		$this->methods->merge( $methods );
		$this->properties->merge( $properties );
	}

	/**
	 * Check if the output contains any methods or properties.
	 *
	 * @return bool Returns true if there is any output, false otherwise.
	 */
	public function isEmpty(): bool {
		return $this->methods->isEmpty() && $this->properties->isEmpty() && $this->traits->isEmpty();
	}

	/**
	 * Add a trait to the output.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param string $trait
	 *
	 * @return void
	 * @throws Exception
	 */
	public function addTrait( string $trait ): void {
		$this->traits->add( $trait, $trait );
	}
}

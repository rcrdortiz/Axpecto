<?php

namespace Axpecto\ClassBuilder;

use Axpecto\Collection\Concrete\Kmap;
use Axpecto\Collection\Concrete\MutableKmap;

/**
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
class BuildContext {

	/**
	 * Constructor for the BuildOutput class.
	 *
	 * @param MutableKmap $methods    List of methods in the output.
	 * @param MutableKmap $properties List of class properties in the output.
	 */
	public function __construct(
		public readonly MutableKmap $methods = new MutableKmap(),
		public readonly MutableKmap $properties = new MutableKmap(),
	) {
	}

	/**
	 * Add a method with its signature and implementation to the output.
	 * Modifies the internal state directly.
	 *
	 * @param string $name           The method name.
	 * @param string $signature      The method signature.
	 * @param string $implementation The method implementation.
	 *
	 * @return void
	 */
	public function addMethod( string $name, string $signature, string $implementation ): void {
		$this->methods->add( $name, "$signature {\n\t\t$implementation\n\t}\n" );
	}

	/**
	 * Add a property to the output.
	 * Modifies the internal state directly.
	 *
	 * @param string $name           The property name.
	 * @param string $implementation The property implementation.
	 *
	 * @return void
	 */
	public function addProperty( string $name, string $implementation ): void {
		$this->properties->add( $name, $implementation );
	}

	/**
	 * Append additional methods and properties to the current output.
	 * Modifies the internal state directly by merging.
	 *
	 * @param Kmap $methods    List of methods to append.
	 * @param Kmap $properties List of properties to append.
	 *
	 * @return void
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
		return $this->methods->isEmpty() && $this->properties->isEmpty();
	}
}

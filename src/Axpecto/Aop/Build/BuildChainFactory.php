<?php

namespace Axpecto\Aop\Build;

use Axpecto\Collection\Concrete\Klist;

/**
 * Class BuildChainFactory
 *
 * A factory class responsible for creating instances of `BuildChain`.
 * This factory takes care of initializing a `BuildChain` with provided annotations, class, method (optional), and output (optional).
 * It uses clean defaults where appropriate, ensuring flexibility and maintainability.
 *
 * - `get()` method: Creates and returns an instance of `BuildChain` with the provided parameters.
 *
 * @package Axpecto\Aop\Build
 */
class BuildChainFactory {

	/**
	 * Creates a new BuildChain instance.
	 *
	 * This method constructs a `BuildChain` using the provided annotations, class name, optional method,
	 * and optional output. It ensures that a new `BuildChain` is created with valid defaults for the output
	 * if not provided.
	 *
	 * @param Klist       $annotations List of annotations for the build chain.
	 * @param BuildOutput $output      The current build output (optional), defaults to a new BuildOutput instance.
	 *
	 * @return BuildChain The initialized BuildChain instance.
	 */
	public function get(
		Klist $annotations,
		BuildOutput $output = new BuildOutput(),
	): BuildChain {
		// Create and return a BuildChain instance
		return new BuildChain( $annotations, $output );
	}
}
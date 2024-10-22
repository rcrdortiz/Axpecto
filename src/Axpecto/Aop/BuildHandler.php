<?php

namespace Axpecto\Aop;

use Axpecto\Aop\Build\BuildChain;
use Axpecto\Aop\Build\BuildOutput;

/**
 * Interface BuildHandler
 *
 * Defines the contract for handling build-related annotations within an Aspect-Oriented Programming (AOP) context.
 * The handler is responsible for intercepting build chains and augmenting the build output.
 */
interface BuildHandler {

	/**
	 * Intercepts the build chain to modify or enhance the build process.
	 *
	 * This method allows the handler to modify the build output by interacting with the build chain
	 * and using information from the provided annotation and current build state.
	 *
	 * @param BuildChain  $chain      The build chain representing the current state of the build process.
	 * @param Annotation  $annotation The annotation triggering the build interception.
	 * @param BuildOutput $output     The current build output, which can be augmented by the handler.
	 *
	 * @return BuildOutput The modified or updated build output.
	 */
	public function intercept(
		BuildChain $chain,
		Annotation $annotation,
		BuildOutput $output
	): BuildOutput;
}

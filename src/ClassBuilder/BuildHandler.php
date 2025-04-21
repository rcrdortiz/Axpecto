<?php

namespace Axpecto\ClassBuilder;

use Axpecto\Annotation\Annotation;

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
	 * @param BuildOutput $buildOutput The current build output, which can be augmented by the handler.
	 *
	 * @return void The modified or updated build output.
	 */
	public function intercept( Annotation $annotation, BuildOutput $buildOutput ): void;
}

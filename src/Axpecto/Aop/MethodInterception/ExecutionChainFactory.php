<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Collection\Concrete\Klist;

/**
 * Class ExecutionChainFactory
 *
 * Responsible for creating instances of `MethodExecutionChain`.
 * This factory provides a centralized way to construct execution chains
 * that manage method interceptions based on annotated behaviors.
 *
 * It ensures that the method execution context and its associated annotations
 * are bundled into a chain that can process annotations and apply their effects.
 *
 * Example usage:
 *
 * ```php
 * $factory = new ExecutionChainFactory();
 * $executionChain = $factory->create($method, $annotations);
 * ```
 *
 * @package Axpecto\Aop\MethodInterception
 */
class ExecutionChainFactory
{
	/**
	 * Creates a new `MethodExecutionChain` instance.
	 *
	 * @param Method $method The method execution context.
	 * @param Klist<MethodExecutionAnnotation> $annotations The list of annotations to be applied to the method.
	 *
	 * @return MethodExecutionChain A configured method execution chain.
	 */
	public function get(
		Method $method,
		Klist $annotations,
	): MethodExecutionChain {
		return new MethodExecutionChain($method, $annotations);
	}
}

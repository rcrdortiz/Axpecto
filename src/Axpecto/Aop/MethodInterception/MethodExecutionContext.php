<?php

namespace Axpecto\Aop\MethodInterception;

use Closure;

/**
 * @template T
 */
class MethodExecutionContext {
	/**
	 * @param string  $class
	 * @param string  $method
	 * @param Closure $methodCall
	 * @param array   $arguments
	 */
	public function __construct(
		public readonly string $class,
		public readonly string $method,
		public readonly Closure $methodCall,
		public readonly array $arguments,
	) {
	}

	public function copy(
		?string $class = null,
		?string $method = null,
		?Closure $methodCall = null,
		?array $arguments = null,
	): MethodExecutionContext {
		return new MethodExecutionContext(
			$class ?? $this->class,
			$method ?? $this->method,
			$methodCall ?? $this->methodCall,
			$arguments ?? $this->arguments,
		);
	}
}
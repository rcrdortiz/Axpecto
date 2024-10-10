<?php

namespace Axpecto\Aop\MethodInterception;

use Closure;

/**
 * Class MethodExecutionContext
 *
 * Represents the execution context of a method, holding details such as the class, method, arguments,
 * and the method call closure.
 *
 */
class Method {

	/**
	 * MethodExecutionContext constructor.
	 *
	 * @param string  $class      The fully qualified name of the class.
	 * @param string  $name       The method name.
	 * @param Closure $methodCall The closure representing the method call.
	 * @param array   $arguments  The arguments passed to the method.
	 */
	public function __construct(
		public readonly string $class,
		public readonly string $name,
		public readonly Closure $methodCall,
		public readonly array $arguments,
	) {
	}

	/**
	 * Resolves the method call with the provided arguments.
	 *
	 * @return mixed The result of the method call.
	 */
	public function call(): mixed {
		return ( $this->methodCall )( ...$this->arguments );
	}

	/**
	 * Creates a copy of the current context with optional modifications to the class, method, method call, or arguments.
	 *
	 * @param string|null  $class      (Optional) New class name.
	 * @param string|null  $method     (Optional) New method name.
	 * @param Closure|null $methodCall (Optional) New method call closure.
	 * @param array|null   $arguments  (Optional) New arguments.
	 *
	 * @return Method   A new instance of the context with updated values.
	 */
	public function copy(
		?string $class = null,
		?string $method = null,
		?Closure $methodCall = null,
		?array $arguments = null,
	): Method {
		return new Method(
			$class ?? $this->class,
			$method ?? $this->name,
			$methodCall ?? $this->methodCall,
			$arguments ?? $this->arguments,
		);
	}
}

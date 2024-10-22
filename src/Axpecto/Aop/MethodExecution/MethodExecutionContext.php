<?php

namespace Axpecto\Aop\MethodExecution;

use Closure;

/**
 * Class MethodExecutionContext
 *
 * Represents the execution context of a method, holding details such as the class name, method name,
 * arguments, and the closure representing the method call. This class provides functionality to
 * invoke the method, manage method arguments, and handle custom return values.
 */
class MethodExecutionContext {
	private mixed $returnValue = null;
	private bool $isReturnValueOverridden = false;
	private bool $isCallable = true;

	/**
	 * MethodExecutionContext constructor.
	 *
	 * @param string  $className  The fully qualified name of the class.
	 * @param string  $methodName The method name.
	 * @param Closure $methodCall The closure representing the method call.
	 * @param array   $arguments  The arguments passed to the method.
	 */
	public function __construct(
		public string $className,
		public string $methodName,
		public Closure $methodCall,
		public array $arguments,
	) {
	}

	/**
	 * Adds or overrides an argument for the method call.
	 *
	 * @param string $name  The argument name.
	 * @param mixed  $value The value of the argument.
	 */
	public function addArgument( string $name, mixed $value ): void {
		$this->arguments[ $name ] = $value;
	}

	/**
	 * Marks the method as callable or non-callable.
	 *
	 * @param bool $isCallable Whether the method should be callable.
	 */
	public function setIsCallable( bool $isCallable ): void {
		$this->isCallable = $isCallable;
	}

	/**
	 * Sets the return value to override the actual method's return value.
	 *
	 * @param mixed $value The overridden return value.
	 */
	public function setReturnValue( mixed $value ): void {
		$this->returnValue = $value;
		$this->isReturnValueOverridden = true;
	}

	/**
	 * Resolves the method call with the provided arguments or returns the overridden value.
	 *
	 * @return mixed The result of the method call or the overridden return value.
	 */
	public function invokeMethod(): mixed {
		if ( $this->isReturnValueOverridden ) {
			return $this->returnValue;
		}

		if ( $this->isCallable ) {
			return ( $this->methodCall )( ...$this->arguments );
		}

		return null;
	}
}

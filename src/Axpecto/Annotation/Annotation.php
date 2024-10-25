<?php

namespace Axpecto\Annotation;

use Attribute;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\MethodExecution\MethodExecutionHandler;

/**
 * Class Annotation
 *
 * Represents an abstract base class for annotations in an Aspect-Oriented Programming (AOP) system.
 * Annotations can have associated handler classes that define how they are processed during
 * method execution or build phases. This class provides mechanisms to retrieve those handlers and
 * associate annotations with specific classes and methods.
 *
 * @package Axpecto\Aop
 */
#[Attribute]
abstract class Annotation {

	/**
	 * The handler for processing the method execution annotation.
	 *
	 * @var MethodExecutionHandler|null
	 */
	protected ?MethodExecutionHandler $methodExecutionHandler = null;

	/**
	 * The builder for the annotation, used during the build phase.
	 *
	 * @var BuildHandler|null
	 */
	protected ?BuildHandler $builder = null;

	/**
	 * The class associated with the annotation.
	 *
	 * @var string|null
	 */
	protected ?string $annotatedClass = null;

	/**
	 * The method associated with the annotation.
	 *
	 * @var string|null
	 */
	protected ?string $annotatedMethod = null;

	/**
	 * Gets the BuildHandler for this annotation, if available.
	 *
	 * @return BuildHandler|null The builder for the annotation, or null if not set.
	 */
	public function getBuilder(): ?BuildHandler {
		return $this->builder;
	}

	/**
	 * Checks if this annotation is meant for the build phase.
	 *
	 * @return bool True if the annotation is used for building, false otherwise.
	 */
	public function isBuildAnnotation(): bool {
		return $this->builder instanceof BuildHandler;
	}

	/**
	 * Gets the MethodExecutionHandler for this annotation, if available.
	 *
	 * @return MethodExecutionHandler|null The handler for method execution, or null if not set.
	 */
	public function getMethodExecutionHandler(): ?MethodExecutionHandler {
		return $this->methodExecutionHandler;
	}

	/**
	 * Checks if this annotation is meant for method execution interception.
	 *
	 * @return bool True if it is a method execution annotation, false otherwise.
	 */
	public function isMethodExecutionAnnotation(): bool {
		return $this->methodExecutionHandler instanceof MethodExecutionHandler;
	}

	/**
	 * Sets the class name that this annotation is associated with.
	 *
	 * @param string $class The class name.
	 *
	 * @return self Returns the current instance for method chaining.
	 */
	public function setAnnotatedClass( string $class ): self {
		$this->annotatedClass = $class;

		return $this;
	}

	/**
	 * Sets the method name that this annotation is associated with.
	 *
	 * @param string $method The method name.
	 *
	 * @return self Returns the current instance for method chaining.
	 */
	public function setAnnotatedMethod( string $method ): self {
		$this->annotatedMethod = $method;

		return $this;
	}

	/**
	 * Gets the class that this annotation is associated with.
	 *
	 * @return string|null The class name, or null if not set.
	 */
	public function getAnnotatedClass(): ?string {
		return $this->annotatedClass;
	}

	/**
	 * Gets the method that this annotation is associated with.
	 *
	 * @return string|null The method name, or null if not set.
	 */
	public function getAnnotatedMethod(): ?string {
		return $this->annotatedMethod;
	}
}

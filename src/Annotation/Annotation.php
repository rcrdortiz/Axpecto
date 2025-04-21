<?php

namespace Axpecto\Annotation;

use Attribute;

/**
 * Class Annotation
 *
 * Represents a base class for annotations in an Aspect-Oriented Programming (AOP) system.
 * Annotations can have associated handler classes that define how they are processed during
 * method execution or build phases. This class provides mechanisms to retrieve those handlers and
 * associate annotations with specific classes and methods.
 *
 * @package Axpecto\Aop
 *
 * @TODO Refactor this and possibly create a hierarchy of annotations with Annotation -> BuildAnnotation -> MethodExecutionAnnotation.
 */
#[Attribute]
class Annotation {
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

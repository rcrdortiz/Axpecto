<?php

namespace Axpecto\Aop;

use Attribute;

/**
 * Class Annotation
 *
 * An abstract base class for annotations in an Aspect-Oriented Programming (AOP) system.
 * Annotations can be associated with specific handler classes that process their logic.
 *
 * @package Axpecto\Aop
 */
#[Attribute]
abstract class Annotation {

	/**
	 * The handler for processing the annotation.
	 *
	 * @var ?AnnotationHandler
	 */
	protected ?AnnotationHandler $handler = null;

	/**
	 * Annotation constructor.
	 *
	 * @param string|null $handlerClass The fully qualified class name of the handler that processes this annotation.
	 */
	public function __construct(
		public readonly ?string $handlerClass = null,
	) {
	}
}

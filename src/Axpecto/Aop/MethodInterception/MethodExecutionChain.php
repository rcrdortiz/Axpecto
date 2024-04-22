<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Aop\Annotation;
use Axpecto\Collection\Concrete\Klist;

/**
 * @template T
 */
class MethodExecutionChain {

	/**
	 * @param MethodExecutionContext $context
	 * @param Klist<Annotation>      $annotations
	 */
	public function __construct(
		protected MethodExecutionContext $context,
		protected Klist $annotations,
	) {
	}

	public function proceed( ?MethodExecutionContext $newContext = null ) {
		$this->context = $newContext ?? $this->context;

		/** @var MethodExecutionAnnotation $annotation */
		$annotation = $this->annotations->nextElement();
		$handler    = $annotation?->getHandler();

		if ( $handler ) {
			return $handler->intercept( $this, $this->context, $annotation );
		}

		return $this->context->methodCall->__invoke( ...$this->context->arguments );
	}
}
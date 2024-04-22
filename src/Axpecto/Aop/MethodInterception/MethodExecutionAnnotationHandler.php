<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Aop\AnnotationHandler;

interface MethodExecutionAnnotationHandler extends AnnotationHandler {
	public function intercept(
		MethodExecutionChain $chain,
		MethodExecutionContext $context,
		MethodExecutionAnnotation $annotation
	): mixed;
}
<?php

namespace Axpecto\Aop\MethodInterception;

class MethodExecutionHandler implements MethodExecutionAnnotationHandler {

	public function intercept( MethodExecutionChain $chain, MethodExecutionContext $context, MethodExecutionAnnotation $annotation ): mixed {
		return $chain->proceed();
	}
}
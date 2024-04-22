<?php

namespace Examples\Caching\Cache\Annotation;

use Attribute;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;

#[Attribute] class InvalidateCache extends MethodExecutionAnnotation {
	public function __construct(
		public readonly string $key,
	){
		parent::__construct( handlerClass: InvalidateCacheMethodInterceptor::class );
	}
}
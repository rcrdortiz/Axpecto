<?php

namespace Examples\Caching\Cache\Annotation;

use Attribute;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;

#[Attribute] class Cache extends MethodExecutionAnnotation {
	public function __construct(
		public readonly ?string $key = null,
		public readonly int $ttl = 1,
	){
		parent::__construct( handlerClass: CacheMethodInterceptor::class );
	}
}
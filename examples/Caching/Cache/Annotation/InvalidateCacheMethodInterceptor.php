<?php

namespace Examples\Caching\Cache\Annotation;

use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotationHandler;
use Axpecto\Aop\MethodInterception\MethodExecutionChain;
use Axpecto\Aop\MethodInterception\MethodExecutionContext;
use Axpecto\Container\Annotation\Singleton;
use Examples\Caching\Cache\CacheInterface;

#[Singleton]
class InvalidateCacheMethodInterceptor implements MethodExecutionAnnotationHandler {

	public function __construct(
		private readonly CacheInterface $cache,
	) {
	}

	public function intercept( MethodExecutionChain $chain, MethodExecutionContext $context, MethodExecutionAnnotation $annotation ): mixed {
		/** @var InvalidateCache $annotation */

		$this->cache->delete(
			namespace: $context->class,
			cache_key: $annotation->key
		);

		return $chain->proceed();
	}
}
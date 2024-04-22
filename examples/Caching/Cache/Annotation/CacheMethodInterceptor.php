<?php

namespace Examples\Caching\Cache\Annotation;

use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotationHandler;
use Axpecto\Aop\MethodInterception\MethodExecutionChain;
use Axpecto\Aop\MethodInterception\MethodExecutionContext;
use Axpecto\Container\Annotation\Singleton;
use Examples\Caching\Cache\CacheInterface;

#[Singleton]
class CacheMethodInterceptor implements MethodExecutionAnnotationHandler {

	public function __construct(
		private readonly CacheInterface $cache,
	) {
	}

	public function intercept( MethodExecutionChain $chain, MethodExecutionContext $context, MethodExecutionAnnotation $annotation ): mixed {
		/** @var Cache $annotation */

		$key = $annotation->key ?? $context->method . ( $context->arguments ? md5( json_encode( $context->arguments ) ) : '' );

		return $this->cache->runCached(
			namespace: $context->class,
			key:       $key,
			action: fn() => $chain->proceed(),
			ttl:       $annotation->ttl,
		);
	}
}
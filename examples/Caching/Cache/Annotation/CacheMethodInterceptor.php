<?php

namespace Examples\Caching\Cache\Annotation;

use Axpecto\Aop\MethodInterception\MethodExecutionAnnotation;
use Axpecto\Aop\MethodInterception\MethodExecutionAnnotationHandler;
use Axpecto\Aop\MethodInterception\MethodExecutionChain;
use Axpecto\Aop\MethodInterception\Method;
use Examples\Caching\Cache\CacheInterface;

class CacheMethodInterceptor implements MethodExecutionAnnotationHandler {

	public function __construct(
		private readonly CacheInterface $cache,
	) {
	}

	public function intercept( MethodExecutionChain $chain, Method $method, MethodExecutionAnnotation $annotation ): mixed {
		/** @var Cache $annotation */

		$key = $annotation->key ?? $chain->getMethod()->name . ( $chain->getMethod()->arguments ? md5( json_encode( $method->arguments ) ) : '' );

		return $this->cache->runCached(
			namespace: $method->class,
			key:       $key,
			action: fn() => $chain->proceed(),
			ttl:       $annotation->ttl,
		);
	}
}
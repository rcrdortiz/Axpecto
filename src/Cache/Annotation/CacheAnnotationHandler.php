<?php

namespace Axpecto\Cache\Annotation;

use Axpecto\Cache\CacheService;
use Axpecto\MethodExecution\MethodExecutionContext;
use Axpecto\MethodExecution\MethodExecutionHandler;
use Override;

class CacheAnnotationHandler implements MethodExecutionHandler {

	public function __construct(
		private readonly CacheService $cacheService
	) {
	}

	#[Override]
	public function intercept( MethodExecutionContext $context ): mixed {
		$annotation = $context->getAnnotation( Cache::class );

		$this->cacheService->enableTelemetry( $annotation->telemetry );

		$cacheKey = $this->getCacheKey( $annotation, $context );

		return $this->cacheService->runCached(
			$cacheKey,
			$annotation->group,
			$annotation->ttl,
			fn() => $context->proceed(),
		);
	}

	private function getCacheKey( Cache $ann, MethodExecutionContext $context ): string {
		return $ann->key ?? $context->className . '::' . $context->methodName . '(' . md5( serialize( $context->arguments ) ) . ')';
	}
}
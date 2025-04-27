<?php

namespace Axpecto\Cache;

use Axpecto\Container\Annotation\DefaultImplementation;
use Closure;

#[DefaultImplementation( InMemoryCacheService::class )]
interface CacheService {

	/**
	 * @param string $cacheKey
	 * @param string|null $group
	 * @param int|null $expiration
	 * @param Closure $lambda
	 *
	 * @return mixed
	 */
	public function runCached( string $cacheKey, ?string $group, ?int $expiration, Closure $lambda ): mixed;

	public function enableTelemetry( bool $enable ): void;
}
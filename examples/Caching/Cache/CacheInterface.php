<?php

namespace Examples\Caching\Cache;

use Closure;

interface CacheInterface {
	public function set( string $namespace, string $key, mixed $data, int $ttl = 0 );

	public function runCached( string $namespace, string $key, Closure $action, int $ttl = 0 );

	public function get( string $namespace, string $key ): mixed;

	public function getAllKeys(): array;

	public function __destruct();

	public function delete( string $namespace, string $cache_key );
}
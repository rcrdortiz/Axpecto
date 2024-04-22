<?php

namespace Examples\Caching\Cache\Implementation;

use Axpecto\Container\Annotation\Singleton;
use Closure;
use Examples\Caching\Cache\CacheInterface;

#[Singleton]
class InMemoryCache implements CacheInterface {

	private array $cache = [];

	public function set( string $namespace, string $key, mixed $data, int $ttl = 0 ) {
		if ( $ttl ) {
			$ttl += time();
		}

		echo "\nCached data for key $key in namespace $namespace.";

		$this->cache[ $namespace ][ $key ] = [ 'data' => $data, 'ttl' => $ttl ];
	}

	public function runCached( string $namespace, string $key, Closure $action, int $ttl = 0 ) {
		$data = $this->get( $namespace, $key );
		if ( $data ) {
			return $data;
		}

		$data = $action();
		$this->set( $namespace, $key, $data, $ttl );

		return $data;
	}

	public function get( string $namespace, string $key ): mixed {
		$record = $this->cache[ $namespace ][ $key ] ?? [ 'data' => null, 'ttl' => 0 ];
		$ttl    = $record['ttl'];
		$data   = $record['data'];

		if ( $ttl && time() > $ttl ) {
			$data = null;
		}

		return $data;
	}

	public function getAllKeys(): array {
		return array_keys( $this->cache );
	}

	public function __destruct() {

	}

	public function delete( string $namespace, string $cache_key ) {
		echo "\nDeleting cache key $cache_key from namespace $namespace.";
		unset( $this->cache[ $namespace ][ $cache_key ] );
	}
}
<?php

namespace Examples\Caching;

use Axpecto\Container\Container;
use Examples\Caching\Cache\Annotation\Cache;
use Examples\Caching\Cache\Annotation\InvalidateCache;
use Examples\Caching\Cache\CacheInterface;
use Examples\Caching\Cache\Implementation\InMemoryCache;

class Application {

	private string $message = '';
	private const DATA_KEY = 'data_key';

	public function __construct( private readonly Container $container ) {
		// We bind the CacheInterface to the InMemoryCache implementation.
		$this->container->bind( CacheInterface::class, InMemoryCache::class );
	}

	#[Cache( ttl: 10 )]
	public function getTime(): int {
		return time();
	}

	#[InvalidateCache( key: self::DATA_KEY )]
	public function setMessage( string $message ): string {
		$this->message  = $message;
		return $this->message;
	}

	#[Cache( key: self::DATA_KEY, ttl: 3600 )]
	public function getMessage(): string {
		return $this->message;
	}
}
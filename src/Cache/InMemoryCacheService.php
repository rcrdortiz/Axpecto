<?php

declare( strict_types=1 );

namespace Axpecto\Cache;

use Axpecto\Telemetry\TelemetryService;
use Closure;
use DateTimeImmutable;
use Override;

class InMemoryCacheService implements CacheService {
	private const string DEFAULT_GROUP = '__default';

	/**
	 * The internal storage:
	 * [
	 *   'groupName' => [
	 *     'cacheKey' => ['value' => mixed, 'expiresAt' => DateTimeImmutable|null],
	 *     …
	 *   ],
	 *   …
	 * ]
	 *
	 * @var array<string, array<string, array{value: mixed, expiresAt: DateTimeImmutable|null}>>
	 */
	private array $store = [];
	private bool $telemetryEnabled;

	public function __construct(
		private readonly TelemetryService $telemetryService,
	) {}

	/**
	 * @inheritDoc
	 */
	#[Override]
	public function runCached(
		string $cacheKey,
		?string $group,
		?int $expiration,
		Closure $lambda
	): mixed {
		$group = $group ?? self::DEFAULT_GROUP;

		// initialize group if needed
		if ( ! isset( $this->store[ $group ] ) ) {
			$this->store[ $group ] = [];
		}

		// return cached if still valid
		if ( isset( $this->store[ $group ][ $cacheKey ] ) ) {
			$entry = $this->store[ $group ][ $cacheKey ];
			if ( $entry['expiresAt'] === null || $entry['expiresAt'] > new DateTimeImmutable() ) {
				$this->recordEvent( hit: true, group: $group, cacheKey: $cacheKey, expires: $entry['expiresAt']?->format( 'c' ) ?? '' );
				return $entry['value'];
			}
			// expired
			unset( $this->store[ $group ][ $cacheKey ] );
		}

		// compute and store
		$value     = $lambda();
		$expiresAt = $expiration !== null
			? ( new DateTimeImmutable() )->modify( "+{$expiration} seconds" )
			: null;

		$this->store[ $group ][ $cacheKey ] = [
			'value'     => $value,
			'expiresAt' => $expiresAt,
		];

		$this->recordEvent( hit: false, group: $group, cacheKey: $cacheKey, expires: $expiresAt?->format( 'c' ) ?? '' );

		return $value;
	}

	/**
	 * Delete a cached entry.
	 *
	 * @param string $cacheKey
	 * @param string|null $group Optional group name (will default to "__default").
	 */
	public function delete( string $cacheKey, ?string $group = null ): void {
		$group = $group ?? self::DEFAULT_GROUP;
		if ( isset( $this->store[ $group ][ $cacheKey ] ) ) {
			unset( $this->store[ $group ][ $cacheKey ] );
		}
	}

	/**
	 * Clear an entire group of cache entries (or all if group is null).
	 *
	 * @param string|null $group
	 */
	public function clear( ?string $group = null ): void {
		if ( $group === null ) {
			$this->store = [];
		} else {
			unset( $this->store[ $group ] );
		}
	}

	public function enableTelemetry( bool $enable ): void {
		$this->telemetryEnabled = $enable;
	}

	private function recordEvent( bool $hit, string $group, string $cacheKey, string $expires ): void {
		$this->telemetryEnabled && $this->telemetryService->recordEvent(
			'in_memory.cache.' . ($hit ? 'hit' : 'miss') . ' => ' . $cacheKey,
			[
				'group'     => $group,
				'cacheKey'  => $cacheKey,
				'expiresAt' => $expires,
			]
		);
	}
}

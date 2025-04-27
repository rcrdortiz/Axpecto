<?php

declare( strict_types=1 );

namespace Axpecto\Telemetry;

use Axpecto\Container\Annotation\DefaultImplementation;
use DateTimeInterface;

/**
 * Interface TelemetryService
 *
 * A generic contract for emitting telemetry data (events, metrics, traces)
 * and managing contextual information for a running application.
 */
#[DefaultImplementation( EchoTelemetryService::class )]
interface TelemetryService {
	/**
	 * Record a generic event with an optional payload.
	 *
	 * @param string $name A short, snake_case event name (e.g. "user_signup").
	 * @param array<string,mixed> $payload Key/value data associated with the event.
	 * @param DateTimeInterface|null $timestamp When the event occurred (default: now).
	 *
	 * @return void
	 */
	public function recordEvent(
		string $name,
		array $payload = [],
		?DateTimeInterface $timestamp = null
	): void;

	/**
	 * Increment (or set) a numeric counter metric.
	 *
	 * @param string $name Metric name (e.g. "http_requests_total").
	 * @param int|float $value Amount to add (default: 1).
	 * @param array<string,string> $labels Labels/dimensions for this metric (e.g. ["method" => "POST"]).
	 *
	 * @return void
	 */
	public function recordCounter(
		string $name,
		int|float $value = 1,
		array $labels = []
	): void;

	/**
	 * Record a gauge metric (arbitrary value), e.g. memory usage.
	 *
	 * @param string $name Metric name (e.g. "memory_usage_bytes").
	 * @param int|float $value Gauge value.
	 * @param array<string,string> $labels Labels/dimensions for this metric.
	 *
	 * @return void
	 */
	public function recordGauge(
		string $name,
		int|float $value,
		array $labels = []
	): void;

	/**
	 * Record a timing/latency measurement.
	 *
	 * @param string $name Metric name (e.g. "db_query_duration_ms").
	 * @param int|float $milliseconds Duration in milliseconds.
	 * @param array<string,string> $labels Labels/dimensions for this measurement.
	 *
	 * @return void
	 */
	public function recordTiming(
		string $name,
		int|float $milliseconds,
		array $labels = []
	): void;

	/**
	 * Begin a named trace/span.
	 *
	 * @param string $spanName A human-readable span name (e.g. "http_request").
	 *
	 * @return string A trace/span identifier to pass to `endTrace()`.
	 */
	public function startTrace( string $spanName ): string;

	/**
	 * End a previously started trace/span, optionally with success/failure status.
	 *
	 * @param string $traceId The ID returned by `startTrace()`.
	 * @param bool $success Whether the operation succeeded (default: true).
	 * @param string|null $error Optional error message if `success === false`.
	 *
	 * @return void
	 */
	public function endTrace(
		string $traceId,
		bool $success = true,
		?string $error = null
	): void;

	/**
	 * Add a key/value pair to the current telemetry context (tags/labels
	 * that will be automatically attached to all subsequent events/metrics).
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function setContext( string $key, string $value ): void;

	/**
	 * Remove a key from the current telemetry context.
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function unsetContext( string $key ): void;

	/**
	 * Flush any buffered telemetry data to the back-end.
	 *
	 * @return void
	 */
	public function flush(): void;
}
<?php

namespace Axpecto\Telemetry;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * A TelemetryService that simply dumps everything to stdout
 * (for demo or debugging purposes).
 */
class EchoTelemetryService implements TelemetryService {

	private array $context = [];

	public function recordEvent(
		string $name,
		array $payload = [],
		?DateTimeInterface $timestamp = null
	): void {
		$when = $timestamp?->format( 'c' ) ?? ( new DateTimeImmutable() )->format( 'c' );
		echo $this->getCurrentTime() . "[Telemetry][Event] $name\n";
		if ( $this->context['verbose'] ?? false ) {
			var_dump( $payload );
		}
	}

	public function recordCounter(
		string $name,
		int|float $value = 1,
		array $labels = []
	): void {
		echo "[Telemetry][Counter] {$name} += {$value}\n";
		var_dump( $labels );
	}

	public function recordGauge(
		string $name,
		int|float $value,
		array $labels = []
	): void {
		echo "[Telemetry][Gauge] {$name} = {$value}\n";
		var_dump( $labels );
	}

	public function recordTiming(
		string $name,
		int|float $milliseconds,
		array $labels = []
	): void {
		echo $this->getCurrentTime() . "[Telemetry][Timing] {$name} took {$milliseconds} ms\n";
		if ( $this->context['verbose'] ?? false ) {
			var_dump( [ 'labels' => $labels, 'context' => $this->context ] );
		}
	}

	public function startTrace( string $spanName ): string {
		$traceId = uniqid( 'trace_', true );
		echo "[Telemetry][Trace:start] {$spanName} → {$traceId}\n";

		return $traceId;
	}

	public function endTrace(
		string $traceId,
		bool $success = true,
		?string $error = null
	): void {
		$status = $success ? 'success' : 'failure';
		echo "[Telemetry][Trace:end] {$traceId} → {$status}\n";
		if ( $error !== null ) {
			echo "  Error: {$error}\n";
		}
	}

	public function setContext( string $key, string $value ): void {
		echo "[Telemetry][Context:set] {$key} = {$value}\n";
		$this->context[ $key ] = $value;
	}

	public function unsetContext( string $key ): void {
		echo "[Telemetry][Context:unset] {$key}\n";
		if ( isset( $this->context[ $key ] ) ) {
			unset( $this->context[ $key ] );
		}
	}

	public function flush(): void {
		echo "[Telemetry][Flush] all buffered data (no-op)\n";
	}

	private function getCurrentTime(): string {
		return "[" . ( new DateTimeImmutable() )->format( 'c' ) . "]";
	}
}
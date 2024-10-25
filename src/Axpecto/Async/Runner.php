<?php

namespace Axpecto\Async;

use Exception;
use Fiber;
use Throwable;

class Runner {

	public function __construct( private readonly string $runnerPath ) {
	}

	/**
	 * @throws Throwable
	 */
	public function run( $class, $method, $serializedArgs ): Fiber {
		echo "Running method asynchronously: $class::$method\n\n";
		$fiber = new Fiber( fn() => $this->runMethod( $class, $method, $serializedArgs ) );
		$fiber->start();

		return $fiber;
	}

	/**
	 * @throws Throwable
	 */
	private function runMethod( $class, $method, $serializedArgs ): mixed {
		$process = proc_open(
			$this->buildCommand( $class, $method, $serializedArgs ),
			[
				0 => [ 'pipe', 'r' ], // stdin
				1 => [ 'pipe', 'w' ], // stdout
				2 => [ 'pipe', 'w' ], // stderr
			],
			$pipes
		);

		if ( ! is_resource( $process ) ) {
			throw new Exception( "Failed to start the command" );
		}

		do {
			Fiber::suspend();
			[ 'running' => $isRunning ] = proc_get_status( $process );
		} while ( $isRunning );

		$output = stream_get_contents( $pipes[1] );

		// Close pipes and process
		foreach ( $pipes as $pipe ) {
			fclose( $pipe );
		}

		$exitCode = proc_close( $process );

		return unserialize( $output );
	}

	public function fireAndForget( string $class, string $method, string $serializedArgs ) {
		$process = proc_open(
			$this->buildCommand( $class, $method, $serializedArgs ),
			[
				0 => [ 'pipe', 'r' ], // stdin
				1 => [ 'pipe', 'w' ], // stdout
				2 => [ 'pipe', 'w' ], // stderr
			],
			$pipes
		);

		// Immediately close pipes to prevent blocking
		if ( is_resource( $process ) ) {
			fclose( $pipes[0] );
			fclose( $pipes[1] );
			fclose( $pipes[2] );
		}
	}

	private function buildCommand( $class, $method, $serializedArgs ): string {
		return 'php ' . $this->runnerPath . ' ' .
		       var_export( $class, true ) . ' ' .
		       var_export( $method, true ) . ' ' .
		       var_export( $serializedArgs, true );
	}
}

<?php

namespace Axpecto\Telemetry\Annotation;

use Axpecto\MethodExecution\MethodExecutionContext;
use Axpecto\MethodExecution\MethodExecutionHandler;
use Axpecto\Telemetry\TelemetryService;
use Override;

class RecordTimingHandler implements MethodExecutionHandler {

	public function __construct(
		private readonly TelemetryService $telemetryService,
	) {
	}

	#[Override]
	public function intercept( MethodExecutionContext $context ): mixed {
		$annotation = $context->getAnnotation( RecordTiming::class );

		if ( ! $annotation->enabled ) {
			return $context->proceed();
		}

		$start    = microtime( true );
		$result   = $context->proceed();
		$end      = microtime( true );
		$duration = $end - $start;

		$this->telemetryService->recordTiming( $context->className . '::' . $context->methodName, $duration * 1000, $annotation->labels );

		return $result;
	}
}
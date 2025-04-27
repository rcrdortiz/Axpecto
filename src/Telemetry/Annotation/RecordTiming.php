<?php

namespace Axpecto\Telemetry\Annotation;

use Attribute;
use Axpecto\Annotation\MethodExecutionAnnotation;
use Axpecto\Container\Annotation\Inject;
use Axpecto\MethodExecution\MethodExecutionHandler;

#[Attribute]
class RecordTiming extends MethodExecutionAnnotation {
	#[Inject( class: RecordTimingHandler::class )]
	protected ?MethodExecutionHandler $methodExecutionHandler;

	public function __construct(
		public readonly bool $enabled = true,
		public readonly array $labels = [],
	) {
	}
}
<?php

namespace Axpecto\Cache\Annotation;

use Attribute;
use Axpecto\Annotation\MethodExecutionAnnotation;
use Axpecto\Container\Annotation\Inject;
use Axpecto\MethodExecution\MethodExecutionHandler;

#[Attribute]
class Cache extends MethodExecutionAnnotation {

	#[Inject( class: CacheAnnotationHandler::class )]
	protected ?MethodExecutionHandler $methodExecutionHandler;

	public function __construct(
		public readonly ?string $key = null,
		public readonly ?string $group = null,
		public readonly ?int $ttl = null,
		public readonly bool $telemetry = false,
	) {
	}
}
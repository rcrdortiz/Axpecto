<?php

namespace Axpecto\Async;

use Attribute;
use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Container\Annotation\Inject;
use Axpecto\MethodExecution\Builder\MethodExecutionBuildHandler;
use Axpecto\MethodExecution\MethodExecutionHandler;

#[Attribute] class Async extends Annotation {
	#[Inject( class: AsyncMethodExecutionHandler::class )]
	protected ?MethodExecutionHandler $methodExecutionHandler;

	#[Inject( class: MethodExecutionBuildHandler::class )]
	protected ?BuildHandler $builder = null;

	public function __construct( public readonly bool $fireAndForget = true ) {}
}
<?php

namespace Axpecto\Aop\MethodInterception;

use Attribute;
use Axpecto\Aop\Annotation;
use Axpecto\Aop\BuildInterception\BuildAnnotation;

#[Attribute( Attribute::TARGET_METHOD )] abstract class MethodExecutionAnnotation extends BuildAnnotation {
	public function __construct( ?string $handlerClass = null ) {
		parent::__construct( MethodExecutionBuilder::class, $handlerClass );
	}
}

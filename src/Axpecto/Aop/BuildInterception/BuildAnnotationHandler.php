<?php

namespace Axpecto\Aop\BuildInterception;

interface BuildAnnotationHandler {
	public function intercept( BuildChain $chain, BuildAnnotation $annotation, string $class ): ?string;
}
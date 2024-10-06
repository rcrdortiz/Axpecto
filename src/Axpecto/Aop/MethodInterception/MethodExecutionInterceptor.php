<?php

namespace Axpecto\Aop\MethodInterception;

use Closure;
use Axpecto\Collection\Concrete\Klist;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;

class MethodExecutionInterceptor {

	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly Container $container,
	) {
	}

	public function intercept(
		string $class,
		string $method,
		Closure $methodCall,
		array $arguments,
	) {
		$annotations = $this->reflect
			->getMethodAnnotations( $class, $method, annotationClass: MethodExecutionAnnotation::class )
			->filter( $this->hasAnnotationHandler( ... ) )
			->map( $this->bindAnnotationHandler( ... ) );

		$arguments = $this->reflect->getMethodArguments( $class, $method, $arguments );

		$context = new MethodExecutionContext( $class, $method, $methodCall, $arguments );
		$chain   = new MethodExecutionChain( $context, $annotations );

		return $chain->proceed();
	}

	private function bindAnnotationHandler( MethodExecutionAnnotation $annotation ): MethodExecutionAnnotation {
		$annotation->setHandler( $this->container->get( $annotation->handlerClass ) );

		return $annotation;
	}

	private function hasAnnotationHandler( MethodExecutionAnnotation $annotation ): bool {
		return $annotation->handlerClass !== null;
	}
}

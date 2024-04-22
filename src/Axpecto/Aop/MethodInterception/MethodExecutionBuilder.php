<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildAnnotationHandler;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionMethod;

class MethodExecutionBuilder implements BuildAnnotationHandler {

	public function __construct(
		protected readonly ReflectionUtils $reflect,
	) {
	}

	public function intercept( BuildChain $chain, BuildAnnotation $annotation, string $class ): ?string {
		$methods = $this->reflect
			->getAnnotatedMethods( $class, with: MethodExecutionAnnotation::class )
			->map( fn( ReflectionMethod $method ) => $this->mapMethodToInterceptedMethod( $method, $class ) )
			->join( separator: "\n\n\t" );

		if ( ! $methods ) {
			return null;
		}

		return "\n\t#[Inject] private MethodExecutionInterceptor \$interceptor;" . $methods;
	}

	private function mapMethodToInterceptedMethod( ReflectionMethod $method, string $class ): string {
		$methodDefinitionString = $this->reflect->getMethodDefinitionString( $method );

		return $methodDefinitionString
		       . "\n\t\t" . ( ( $method->getReturnType()?->getName() !== 'void' ) ? 'return ' : '' )
		       . "\$this->interceptor->intercept( "
		       . "\n\t\t\tclass: '$class',"
		       . "\n\t\t\tmethod: '{$method->getName()}',"
		       . "\n\t\t\tmethodCall: parent::{$method->getName()}( ... ),"
		       . "\n\t\t\targuments: func_get_args(),"
		       . "\n\t\t);"
		       . "\n\t}";
	}
}
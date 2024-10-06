<?php

namespace Axpecto\Aop\MethodInterception;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildAnnotationHandler;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Aop\BuildInterception\BuildOutput;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Container\Annotation\Singleton;
use Axpecto\Reflection\ReflectionUtils;

#[Singleton]
class MethodExecutionBuilder implements BuildAnnotationHandler {

	public function __construct(
		protected readonly ReflectionUtils $reflect,
	) {
	}

	public function intercept( BuildChain $chain, BuildAnnotation $annotation, string $class, ?string $method, BuildOutput $output ): BuildOutput {
		$method                 = $this->reflect->getReflectionClass( $class )->getMethod( $method );
		$return                 = $method->hasReturnType() && $method->getReturnType()->getName() !== 'void' ? "return" : '';
		$methodDefinitionString = $this->reflect->getMethodDefinitionString( $method )
		                          . "{ \n\t\t $return \$this->interceptor->intercept( "
		                          . "\n\t\t\tclass: '$class',"
		                          . "\n\t\t\tmethod: '{$method->getName()}',"
		                          . "\n\t\t\tmethodCall: parent::{$method->getName()}( ... ),"
		                          . "\n\t\t\targuments: func_get_args(),"
		                          . "\n\t\t); \n\t}";

		$output = $output->append(
			methods:       [ $method->getName() => $methodDefinitionString ],
			useStatements: [
				               MethodExecutionInterceptor::class,
				               Inject::class,
			               ],
			properties:    [ "#[Inject] private MethodExecutionInterceptor \$interceptor;" ],
		);

		return $chain->proceed( $output );
	}
}
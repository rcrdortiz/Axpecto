<?php

namespace Axpecto\Aop;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionException;
use ReflectionMethod;

/**
 * @template T
 */
class InterceptedClassFactory {

	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly Container $container,
	) {
	}

	/**
	 * Registers an AOP proxied class and returns the class name.
	 *
	 * @param class-string<T> $class *
	 *
	 * @throws ReflectionException|ReflectionException
	 *
	 * @return string
	 */
	public function getClassname( string $class ): string {
		$classAnnotations = $this->reflect
			->getClassAnnotations( $class, BuildAnnotation::class )
			->map( $this->bindAnnotationHandler( ... ) );
		$chain            = new BuildChain( $classAnnotations, $class );
		$classBuildCode   = $chain->proceed();

		$methods = $this->reflect->getAnnotatedMethods( $class, with: BuildAnnotation::class );

		if ( $methods->isEmpty() && ! $classBuildCode ) {
			return $class;
		}

		$methodString = $methods->map( function ( ReflectionMethod $method ) use ( $class ) {
			$methodDefinitionString = $this->reflect->getMethodDefinitionString( $method );

			return $methodDefinitionString
			       . " {"
			       . "\n\t\t" . ( ( $method->getReturnType()?->getName() !== 'void' ) ? 'return ' : '' )
			       . "\$this->interceptor->intercept( "
			       . "\n\t\t\tclass: '$class',"
			       . "\n\t\t\tmethod: '{$method->getName()}',"
			       . "\n\t\t\tmethodCall: parent::{$method->getName()}( ... ),"
			       . "\n\t\t\targuments: func_get_args(),"
			       . "\n\t\t); \n\t}";

		} )->join( separator: "\n\n\t" );

		$className    = str_replace( "\\", '_', "{$class}__Intercepted" );
		$proxiedClass = "use Axpecto\Container\Annotation\Inject;"
		                . "\nuse Axpecto\Aop\MethodInterception\MethodExecutionInterceptor;"
		                . "\n\nclass $className extends $class "
		                . "{\n\t#[Inject] private MethodExecutionInterceptor \$interceptor;"
		                . "\n\n\t$classBuildCode"
		                . "\n\n\t$methodString \n};";

		eval( $proxiedClass );

		return $className;
	}

	private function bindAnnotationHandler( BuildAnnotation $annotation ): BuildAnnotation {
		if ( ! $annotation->builderClass ) {
			return $annotation;
		}

		$annotation->setBuilder( $this->container->get( $annotation->builderClass ) );

		return $annotation;
	}
}
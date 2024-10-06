<?php

namespace Axpecto\Aop;

use Axpecto\Aop\BuildInterception\BuildAnnotation;
use Axpecto\Aop\BuildInterception\BuildChain;
use Axpecto\Aop\BuildInterception\BuildOutput;
use Axpecto\Aop\Exception\ClassAlreadyBuiltException;
use Axpecto\Container\Container;
use Axpecto\Reflection\ReflectionUtils;
use ReflectionException;
use ReflectionMethod;

/**
 * @template T
 */
class ClassBuilder {

	public function __construct(
		private readonly ReflectionUtils $reflect,
		private readonly Container $container,
		private array $builtClasses = [],
	) {
	}

	/**
	 * Registers an AOP proxied class and returns the class name.
	 *
	 * @param class-string<T> $class *
	 *
	 * @return string
	 * @throws ReflectionException|ReflectionException
	 * @throws ClassAlreadyBuiltException
	 *
	 */
	public function build( string $class ): string {
		if ( isset( $this->builtClasses[ $class ] ) ) {
			throw new ClassAlreadyBuiltException( $class );
		}

		$classAnnotations = $this->reflect
			->getClassAnnotations( $class, BuildAnnotation::class )
			->foreach( $this->bindAnnotationHandler( ... ) );
		$buildOutput      = ( new BuildChain( $classAnnotations, $class ) )->proceed();

		$methods = $this->reflect->getAnnotatedMethods( $class, with: BuildAnnotation::class );
		foreach ( $methods as $method ) {
			/** @var ReflectionMethod $method */
			$annotations = $this->reflect->getMethodAnnotations( $class, $method->getName(), BuildAnnotation::class )
			                             ->map( $this->bindAnnotationHandler( ... ) );

			$buildOutput = ( new BuildChain( $annotations, $class, $method->getName() ) )->proceed( $buildOutput );
		}

		if ( ! $buildOutput->hasOutput() ) {
			return $class;
		}

		$reflection      = $this->reflect->getReflectionClass( $class );
		$inheritanceType = $reflection->isInterface() ? 'implements' : 'extends';
		$className       = str_replace( "\\", '_', $class );
		$proxiedClass    = $this->getBuildUses( $buildOutput )
		                   . "\n\nclass $className $inheritanceType $class {"
		                   . $this->getBuildProperties( $buildOutput )
		                   . $this->getBuildMethods( $buildOutput )
		                   . "\n}";

		eval( $proxiedClass );

		$this->builtClasses[ $class ] = $className;

		return $className;
	}

	private function bindAnnotationHandler( BuildAnnotation $annotation ): BuildAnnotation {
		if ( ! $annotation->builderClass ) {
			return $annotation;
		}

		$annotation->setBuilder( $this->container->get( $annotation->builderClass ) );

		return $annotation;
	}

	private function getBuildMethods( BuildOutput $buildOutput ) {
		if ( ! $buildOutput->hasOutput() ) {
			return '';
		}

		return "\n\n\t" . listFrom( array_unique( $buildOutput->methods ) )->join( "\n\t" );
	}

	private function getBuildProperties( BuildOutput $buildOutput ) {
		if ( ! $buildOutput->hasOutput() ) {
			return '';
		}

		return "\n\t" . listFrom( array_unique( $buildOutput->properties ) )->join( "\n\t" );
	}

	private function getBuildUses( BuildOutput $buildOutput ) {
		if ( ! $buildOutput->hasOutput() ) {
			return '';
		}

		return "\n" . listFrom( array_unique( $buildOutput->useStatements ) )
				->map( fn( $use ) => "use $use;" )
				->join( "\n" );
	}
}
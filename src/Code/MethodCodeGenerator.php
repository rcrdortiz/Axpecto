<?php

namespace Axpecto\Code;

use Axpecto\Reflection\ReflectionUtils;
use Exception;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Generates PHP method signatures from reflection.
 */
class MethodCodeGenerator {

	public function __construct(
		private readonly ReflectionUtils $reflectionUtils,
	) {
	}

	/**
	 * Generate a method signature (visibility, name, args, return type).
	 *
	 * @param class-string $class The fully qualified class name.
	 * @param string $method The method name.
	 *
	 * @return string The generated method signature.
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function implementMethodSignature( string $class, string $method ): string {
		$rMethod = $this->reflectionUtils->getClassMethod( $class, $method );

		if ( $rMethod->isPrivate() || ! $rMethod->isAbstract() ) {
			throw new Exception( "Can't implement non-abstract or private method $class::{$method}()" );
		}

		$visibility = $rMethod->isPublic() ? 'public' : 'protected';

		$arguments = listFrom( $rMethod->getParameters() )
			->map( $this->mapParameterToCode( ... ) )
			->join( ', ' );

		$return = $rMethod->hasReturnType()
			? ': ' . $this->mapReturnTypeToCode( $rMethod->getReturnType() )
			: '';

		return "$visibility function {$method}($arguments)$return";
	}

	/**
	 * Convert a ReflectionParameter to a PHP argument definition string.
	 *
	 * @param ReflectionParameter $parameter
	 *
	 * @return string
	 */
	public function mapParameterToCode( ReflectionParameter $parameter ): string {
		$code = '';

		if ( $parameter->hasType() ) {
			$code .= $this->mapReturnTypeToCode( $parameter->getType() ) . ' ';
		}

		if ( $parameter->isPassedByReference() ) {
			$code .= '&';
		}

		if ( $parameter->isVariadic() ) {
			$code .= '...';
		}

		$code .= '$' . $parameter->getName();

		if ( $parameter->isDefaultValueAvailable() && ! $parameter->isVariadic() ) {
			$default = var_export( $parameter->getDefaultValue(), true );
			$code    .= " = $default";
		}

		return $code;
	}

	/**
	 * Convert a ReflectionType (return or parameter) to a PHP type declaration.
	 *
	 * @param ReflectionType|null $type
	 *
	 * @return string
	 */
	public function mapReturnTypeToCode( ?ReflectionType $type ): string {
		if ( ! $type ) {
			return '';
		}

		if ( $type instanceof ReflectionNamedType ) {
			$nullable = $type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '';

			return $nullable . $type->getName();
		}

		if ( $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType ) {
			$separator = $type instanceof ReflectionUnionType ? '|' : '&';

			return listFrom( $type->getTypes() )
				->map( fn( ReflectionType $t ) => $this->mapReturnTypeToCode( $t ) )
				->join( $separator );
		}

		return '';
	}
}

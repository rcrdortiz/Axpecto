<?php

declare( strict_types=1 );

namespace Axpecto\Code;

use Axpecto\Reflection\ReflectionUtils;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * AnnotationCodeGenerator
 * @TODO Refactor this whole class implementation. Current implementation level is PoC.
 *
 * Turn any PHP-8 attribute object into its source-code form, e.g.
 *   #[MyAttr(foo: 'bar', flags: [1,2,3])]
 *
 * Supports nested attributes, named arguments, default skipping, arrays, scalars, nulls, etc.
 */
class AnnotationCodeGenerator {
	public function __construct(
		private readonly ReflectionUtils $reflect,
	) {
	}

	/**
	 * Serialize a PHP-8 attribute (annotation) instance into its `#[Name(...)]` syntax.
	 *
	 * @param object $annotation An instance of a class marked with `#[Attribute]`
	 *
	 * @return string            The `#[ShortClassName(arg: value, ...)]` declaration
	 * @throws InvalidArgumentException|ReflectionException On unsupported argument types
	 */
	public function serializeAnnotation( object $annotation ): string {
		$refClass  = $this->reflect->getReflectionClass( $annotation::class );

		$ctor   = $refClass->getConstructor();
		$params = $ctor ? $ctor->getParameters() : [];

		$parts = [];
		foreach ( $params as $param ) {
			$name = $param->getName();
			// read the public property of the same name
			$value = $annotation->$name;

			// skip if matches default
			if ( $param->isDefaultValueAvailable() &&
			     $param->getDefaultValue() === $value
			) {
				continue;
			}

			if ( $param->isVariadic() ) {
				$parts[] = '...' . $this->serializeValue( $value );
			} else {
				$parts[] = $name . ': ' . $this->serializeValue( $value );
			}
		}

		return $annotation::class . '(' . implode( ', ', $parts ) . ')';
	}

	/**
	 * Recursively serialize PHP values into valid PHP code.
	 *
	 * @param mixed $v
	 *
	 * @return string
	 * @throws InvalidArgumentException|ReflectionException
	 */
	private function serializeValue( mixed $v ): string {
		if ( is_array( $v ) ) {
			$items = [];
			foreach ( $v as $k => $e ) {
				if ( is_string( $k ) ) {
					$items[] = var_export( $k, true ) . ' => ' . $this->serializeValue( $e );
				} else {
					$items[] = $this->serializeValue( $e );
				}
			}

			return '[' . implode( ', ', $items ) . ']';
		}

		if ( is_string( $v ) || is_int( $v ) || is_float( $v ) || is_bool( $v ) || is_null( $v ) ) {
			if ( is_string( $v ) || is_null( $v ) ) {
				return var_export( $v, true );
			}

			return $v ? 'true' : ( $v === false ? 'false' : (string) $v );
		}

		// nested attribute?
		if ( is_object( $v ) ) {
			$nestedRef = new ReflectionClass( $v );
			if ( $nestedRef->isAttribute() ) {
				// strip leading "#[" and trailing "]" from nested serialization
				$raw = $this->serializeAnnotation( $v );

				return substr( $raw, 2, - 1 );
			}
		}

		throw new InvalidArgumentException( 'Cannot serialize value of type ' . get_debug_type( $v ) );
	}
}

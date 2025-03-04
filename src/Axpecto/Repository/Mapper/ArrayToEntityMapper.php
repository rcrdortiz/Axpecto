<?php

namespace Axpecto\Repository\Mapper;

use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Storage\Criteria\Mapping;
use Exception;
use ReflectionException;

/**
 * Class ArrayToEntityMapper
 *
 * Maps an associative array to an entity instance by reading the constructor
 * arguments and any Mapping annotations defined on them. This ensures that the
 * array data is properly mapped to the corresponding database fields.
 *
 * @package Axpecto\Repository\Mapper
 */
final class ArrayToEntityMapper {

	public function __construct(
		private readonly ReflectionUtils $reflect
	) {
	}

	/**
	 * Maps an array to an entity.
	 *
	 * @param class-string $entityClass Fully qualified class name of the entity.
	 * @param array        $data        The source data array.
	 *
	 * @return object An instance of the entity populated with data.
	 * @throws ReflectionException
	 * @throws Exception If an argument cannot be resolved.
	 */
	public function map( string $entityClass, array $data ): object {
		$constructorArgs = $this->reflect
			->getConstructorArguments( $entityClass )
			->map( fn( Argument $param ) => $this->resolveArgument( $entityClass, $param, $data ) );

		return new $entityClass( ...$constructorArgs->toArray() );
	}

	/**
	 * Resolves an argument value from the data array, checking for a Mapping annotation.
	 *
	 * @param class-string $entityClass
	 * @param Argument     $arg
	 * @param array        $data
	 *
	 * @return mixed
	 *
	 * @throws Exception if the argument cannot be resolved.
	 */
	protected function resolveArgument( string $entityClass, Argument $arg, array $data ) {
		$paramName = $arg->name;
		// Check for a Mapping annotation on the parameter.
		$mapping = $this->reflect->getParamAnnotations(
			$entityClass,
			'__construct',
			$paramName,
			Mapping::class
		)->firstOrNull();

		$key = $mapping?->fromField ?? $paramName;

		// Try an exact match using the key.
		if ( array_key_exists( $key, $data ) ) {
			return $data[ $key ];
		}
		// Next, try converting camelCase parameter name to snake_case.
		$snakeKey = $this->camelToSnake( $key );
		if ( array_key_exists( $snakeKey, $data ) ) {
			return $data[ $snakeKey ];
		}
		// Alternatively, try an uppercase version.
		$upperKey = strtoupper( $key );
		if ( array_key_exists( $upperKey, $data ) ) {
			return $data[ $upperKey ];
		}
		// Use default value if available.
		if ( $arg->default !== null ) {
			return $arg->default;
		}
		// Otherwise, throw an exception.
		throw new Exception(
			"Cannot resolve argument {$paramName} for entity {$entityClass}. Available data keys: " . implode( ', ', array_keys( $data ) )
		);
	}

	/**
	 * Converts a camelCase string to snake_case.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	protected function camelToSnake( string $input ): string {
		return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $input ) );
	}
}

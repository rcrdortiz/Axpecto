<?php

namespace Axpecto\Repository\Mapper;

use Axpecto\Storage\Entity\EntityField;
use Axpecto\Storage\Entity\EntityMetadataService;
use Exception;
use ReflectionException;

/**
 * @psalm-suppress PossiblyUnusedMethod Class used by generated Repository implementations.
 * Class ArrayToEntityMapper
 *
 * Maps an associative array to an entity instance by reading the constructor
 * arguments and any Mapping annotations defined on them. This ensures that the
 * array data is properly mapped to the corresponding database fields.
 *
 * @package Axpecto\Repository\Mapper
 */
final class ArrayToEntityMapper {

	/***
	 * @param EntityMetadataService $metadataService
	 */
	public function __construct(
		private readonly EntityMetadataService $metadataService,
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
	public function mapEntityFromArray( string $entityClass, array $data ): object {
		$arguments = $this->metadataService
			->getFields( $entityClass )
			->map( fn( EntityField $field ) => $this->getValueForField( $field, $data ) );

		return new $entityClass( ...$arguments->toArray() );
	}

	/**
	 * Resolves an argument value from the data array, checking for a Mapping annotation.
	 *
	 * @param EntityField $field
	 * @param array       $data
	 *
	 * @return mixed
	 *
	 * @throws Exception if the argument cannot be resolved.
	 */
	protected function getValueForField( EntityField $field, array $data ): mixed {
		if ( array_key_exists( $field->name, $data ) ) {
			return $data[ $field->name ];
		}

		if ( array_key_exists( $field->persistenceMapping, $data ) ) {
			return $data[ $field->persistenceMapping ];
		}

		if ( $field->default !== EntityField::NO_DEFAULT_VALUE_SPECIFIED ) {
			return $field->default;
		}

		throw new Exception(
			"Cannot resolve argument {$field->name} for entity {$field->entityClass}. Available data keys: " . implode( ', ',
			                                                                                                            array_keys( $data ) )
		);
	}
}

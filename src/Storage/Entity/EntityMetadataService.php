<?php

namespace Axpecto\Storage\Entity;

use Axpecto\Collection\Klist;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Storage\Entity\Column\Column;
use Exception;
use ReflectionException;

class EntityMetadataService {

	private const CONSTRUCTOR_METHOD = '__construct';

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param ReflectionUtils $reflectionUtils
	 */
	public function __construct(
		private readonly ReflectionUtils $reflectionUtils,
	) {
	}

	/**
	 * @param string $entityClass
	 *
	 * @return Klist<EntityField>
	 * @throws ReflectionException
	 */
	public function getFields( string $entityClass ): Klist {
		return $this->reflectionUtils
			->getConstructorArguments( $entityClass )
			->map( fn( Argument $arg ) => $this->mapArgumentToEntityField( $arg, $entityClass ) );
	}

	/**
	 * Retrieves entity metadata (Entity attribute) from the given entity class.
	 *
	 * @param class-string<Entity> $entityClass
	 *
	 * @return Entity
	 * @throws Exception
	 */
	public function getEntity( string $entityClass ): Entity {
		$entityAnnotation = $this->reflectionUtils
			->getClassAnnotations( $entityClass, Entity::class )
			->firstOrNull();

		if ( ! $entityAnnotation ) {
			throw new Exception( "Entity annotation missing on class $entityClass" );
		}

		return $entityAnnotation;
	}

	private function mapArgumentToEntityField( Argument $argument, string $entity ): EntityField {
		/* @var Column $column */
		$column = $this->reflectionUtils->getParamAnnotations(
			$entity,
			self::CONSTRUCTOR_METHOD,
			$argument->name,
			Column::class
		)->firstOrNull();

		return new EntityField(
			name:               $argument->name,
			type:               $column?->type ?? $argument->type,
			nullable:           $column?->isNullable ?? $argument->nullable,
			entityClass:        $entity,
			default:            $column?->defaultValue ?? $argument->default ?? EntityField::NO_DEFAULT_VALUE_SPECIFIED,
			persistenceMapping: $column?->toField ?? $argument->name,
			isAutoIncrement:    $column?->autoIncrement ?? false,
			isPrimary:          $column?->isPrimary ?? false,
			isUnique:           $column?->isUnique ?? false,
			isIndexed:          $column?->isIndexed ?? false,
			onUpdate:           $column?->onUpdate ?? false,
		);
	}
}
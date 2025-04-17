<?php

declare( strict_types=1 );

namespace Axpecto\Repository;

use Attribute;
use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Container\Annotation\Inject;
use Axpecto\Repository\Handler\RepositoryBuildHandler;
use Axpecto\Storage\Entity\Entity as EntityAttribute;

/**
 * Repository attribute.
 *
 * Marks a class as a repository for a specific entity.
 * The repository build handler (injected via dependency injection) processes the annotated class.
 *
 * @template T of EntityAttribute
 */
#[Attribute( Attribute::TARGET_CLASS )]
class Repository extends Annotation {
	/**
	 * The build handler instance responsible for processing the repository.
	 *
	 * @var BuildHandler|null
	 */
	#[Inject( class: RepositoryBuildHandler::class )]
	protected ?BuildHandler $builder = null;

	/**
	 * Constructor.
	 *
	 * @param class-string<EntityAttribute> $entityClass The fully qualified class name of the entity associated with this repository.
	 */
	public function __construct(
		public readonly string $entityClass,
	) {
	}
}

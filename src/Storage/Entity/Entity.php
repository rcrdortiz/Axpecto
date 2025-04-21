<?php

namespace Axpecto\Storage\Entity;

use Attribute;
use Axpecto\Annotation\Annotation;
use Axpecto\Storage\Criteria\CriteriaPersistenceStrategy;

#[Attribute( Attribute::TARGET_CLASS )]
class Entity extends Annotation {

	/**
	 * Constructor.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param class-string<CriteriaPersistenceStrategy> $storage The persistence strategy class name.
	 */
	public function __construct(
		public readonly string $storage,
		public readonly ?string $table = null,
		public readonly ?string $idField = 'id',
	) {
	}
}

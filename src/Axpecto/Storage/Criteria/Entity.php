<?php

namespace Axpecto\Storage\Criteria;

use Attribute;

#[Attribute( Attribute::TARGET_CLASS )]
class Entity {

	/**
	 * Constructor.
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

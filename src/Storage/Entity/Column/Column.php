<?php

namespace Axpecto\Storage\Entity\Column;

use Attribute;
use Axpecto\Storage\Entity\EntityField;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
#[Attribute( Attribute::TARGET_PARAMETER )]
class Column {
	const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

	public function __construct(
		public readonly ?string $name = null,
		public readonly ?bool $isPrimary = false,
		public readonly bool $isUnique = false,
		public readonly bool $isIndexed = false,
		public readonly bool $autoIncrement = false,
		public readonly bool $isTimestamp = false,
		public readonly bool $isNullable = true,
		public readonly ?string $type = null,
		public readonly mixed $defaultValue = EntityField::NO_DEFAULT_VALUE_SPECIFIED,
		public readonly ?string $onUpdate = null,
	) {
	}
}
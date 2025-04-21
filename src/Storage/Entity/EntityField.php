<?php

namespace Axpecto\Storage\Entity;

/**
 * @psalm-suppress UnusedProperty
 */
class EntityField {
	const NO_DEFAULT_VALUE_SPECIFIED = 'NO_DEFAULT_VALUE_SPECIFIED';

	public function __construct(
		public readonly string $name,
		public readonly ?string $type,
		public readonly bool $nullable,
		public readonly ?string $entityClass,
		public readonly mixed $default = self::NO_DEFAULT_VALUE_SPECIFIED,
		public readonly ?string $persistenceMapping = null,
		public readonly bool $isAutoIncrement = false,
		public readonly bool $isPrimary = false,
		public readonly bool $isUnique = false,
		public readonly bool $isIndexed = false,
		public readonly ?string $onUpdate = null,
	) {
	}
}
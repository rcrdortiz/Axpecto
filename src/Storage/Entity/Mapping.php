<?php

namespace Axpecto\Storage\Entity;

use Attribute;

#[Attribute( Attribute::TARGET_PARAMETER )]
class Mapping {
	/**
	 * Constructor.
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @TODO Remove this class once we've migrated the build handler to use the new Column system.
	 *
	 * @param string $toField
	 */
	public function __construct(
		public readonly string $toField,
	) {
	}
}
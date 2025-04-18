<?php

namespace Axpecto\Storage\Entity;

use Attribute;

#[Attribute( Attribute::TARGET_PARAMETER )]
class Mapping {
	/**
	 * Constructor.
	 *
	 * @param string $toField
	 */
	public function __construct(
		public readonly string $toField,
	) {
	}
}
<?php

namespace Axpecto\Storage\Criteria;

use Attribute;

#[Attribute( Attribute::TARGET_PARAMETER )]
class Mapping {
	/**
	 * Constructor.
	 *
	 * @param string $fromField
	 */
	public function __construct(
		public readonly string $fromField,
	) {
	}
}
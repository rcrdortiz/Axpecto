<?php

namespace Axpecto\Storage\Entity\Column;

use Attribute;

#[Attribute( Attribute::TARGET_PARAMETER )]
class Id extends Column {
	/**
	 * Constructor.
	 *
	 * @param bool $autoIncrement
	 */
	public function __construct( bool $autoIncrement = true ) {
		parent::__construct(
			isPrimary:     true,
			autoIncrement: $autoIncrement,
			isNullable:    false,
		);
	}
}

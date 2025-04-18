<?php

namespace Axpecto\Storage\Entity\Column;

use Attribute;

#[Attribute( Attribute::TARGET_PARAMETER )]
class UniqueNotNull extends Column {
	public function __construct() {
		parent::__construct(
			isUnique:   true,
			isNullable: false,
		);
	}
}
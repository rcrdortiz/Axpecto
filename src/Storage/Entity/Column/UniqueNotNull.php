<?php

namespace Axpecto\Storage\Entity\Column;

use Attribute;

/**
 * @psalm-suppress PossiblyUnusedClass This class is used by the build system or clients.
 */
#[Attribute( Attribute::TARGET_PARAMETER )]
class UniqueNotNull extends Column {
	public function __construct() {
		parent::__construct(
			isUnique:   true,
			isNullable: false,
		);
	}
}
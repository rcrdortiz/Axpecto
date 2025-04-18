<?php

namespace Axpecto\Storage\Entity\Column;

use Attribute;

/**
 * @psalm-suppress PossiblyUnusedClass This class is used by the build system or clients.
 */
#[Attribute( Attribute::TARGET_PARAMETER )]
class Timestamp extends Column {
	public function __construct( $onUpdate = null ) {
		parent::__construct(
			isNullable:   false,
			defaultValue: Column::CURRENT_TIMESTAMP,
			onUpdate:     $onUpdate,
		);
	}
}
<?php

namespace Axpecto\Storage\Entity\Column;

use Attribute;

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
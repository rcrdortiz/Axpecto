<?php

namespace Axpecto\Container\Annotation;

use Attribute;
use Axpecto\Aop\Annotation;

#[Attribute( Attribute::TARGET_PROPERTY )] class Inject extends Annotation {
	public function __construct(
		public readonly array $args = [],
	) {
		parent::__construct();
	}
}
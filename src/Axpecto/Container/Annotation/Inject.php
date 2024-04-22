<?php

namespace Axpecto\Container\Annotation;

use Attribute;
use Axpecto\Aop\Annotation;

#[Attribute( Attribute::TARGET_PROPERTY )] class Inject extends Annotation {
}
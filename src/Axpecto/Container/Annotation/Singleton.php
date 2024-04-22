<?php

namespace Axpecto\Container\Annotation;

use Attribute;
use Axpecto\Aop\Annotation;

#[Attribute( Attribute::TARGET_CLASS )] class Singleton extends Annotation {
}

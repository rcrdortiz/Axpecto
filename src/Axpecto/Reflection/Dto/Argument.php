<?php

namespace Axpecto\Reflection\Dto;

class Argument {
	public function __construct(
		public readonly string $name,
		public readonly ?string $type,
		public readonly mixed $default = null
	){}
}
<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Storage\Criteria\LogicOperator;
use Axpecto\Storage\Criteria\Operator;

class ParsedMethodPart {

	public function __construct(
		public readonly Prefix $prefix,
		public readonly LogicOperator $logicOperator,
		public readonly string $field,
		public readonly Operator $operator,
	) {
	}
}
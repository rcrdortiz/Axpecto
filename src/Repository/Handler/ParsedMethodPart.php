<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Storage\Criteria\LogicOperator;
use Axpecto\Storage\Criteria\Operator;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
readonly class ParsedMethodPart {
	public function __construct(
		public Prefix $prefix,
		public LogicOperator $logicOperator,
		public string $field,
		public Operator $operator,
	) {
	}

	public function copy(
		?Prefix $prefix = null,
		?LogicOperator $logicOperator = null,
		?string $field = null,
		?Operator $operator = null,
	): self {
		return new self(
			prefix: $prefix ?? $this->prefix,
			logicOperator: $logicOperator ?? $this->logicOperator,
			field: $field ?? $this->field,
			operator: $operator ?? $this->operator,
		);
	}
}
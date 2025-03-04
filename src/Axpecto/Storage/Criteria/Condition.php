<?php

namespace Axpecto\Storage\Criteria;

/**
 * Represents a single condition in a Criteria object.
 */
class Condition {
	/**
	 * @param string        $field    The name of the field.
	 * @param mixed         $value    The value to compare (can be null for operators like IsNull).
	 * @param Operator      $operator The operator (e.g., Equals, GreaterThan, etc.).
	 * @param LogicOperator $logic    The logic operator to combine with previous conditions.
	 */
	public function __construct(
		public string $field,
		public mixed $value = null,
		public Operator $operator = Operator::EQUALS,
		public LogicOperator $logic = LogicOperator::AND,
	) {
	}
}

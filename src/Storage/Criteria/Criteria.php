<?php

namespace Axpecto\Storage\Criteria;

use Axpecto\Collection\Klist;
use Axpecto\Collection\Kmap;

/**
 * @psalm-suppress PossiblyUnusedMethod Used by generated code and/or clients.
 *
 * A criteria builder that supports multiple condition operators and logic operators.*
 */
class Criteria {

	/**
	 * @var Klist<Condition> Conditions for the query.
	 */
	private Klist $conditions;

	/**
	 * The maximum number of records to return.
	 */
	private ?int $limit;

	/**
	 * @var Kmap<string, OrderType> Ordering instructions for the query.
	 */
	private Kmap $ordering;

	/**
	 * Constructor.
	 *
	 * @param Klist<Condition>|null        $conditions
	 * @param int|null                     $limit
	 * @param Kmap<string, OrderType>|null $ordering
	 */
	public function __construct(
		?Klist $conditions = null,
		?int $limit = null,
		?Kmap $ordering = null
	) {
		$this->conditions = $conditions ?? mutableEmptyList();
		$this->limit      = $limit;
		$this->ordering   = $ordering ?? mutableEmptyMap();
	}

	/**
	 * Add a new condition to the criteria.
	 *
	 * @param string             $field    The field name.
	 * @param mixed              $value    The value for the condition.
	 * @param Operator|null      $operator The operator to use (defaults to Operator::EQUALS).
	 * @param LogicOperator|null $logic    The logic operator to combine with previous conditions (defaults to LogicOperator::AND).
	 *
	 * @return static
	 * @throws \Exception
	 */
	public function addCondition( string $field, mixed $value, ?Operator $operator = null, ?LogicOperator $logic = null ): static {
		$condition = new Condition(
			$field,
			$value,
			$operator ?? Operator::EQUALS,
			$logic ?? LogicOperator::AND
		);
		$this->conditions->add( $condition );

		return $this;
	}

	/**
	 * Get all conditions.
	 *
	 * @return Klist<Condition>
	 */
	public function getConditions(): Klist {
		return $this->conditions;
	}

	/**
	 * Set the limit for the query.
	 *
	 * @param int $limit
	 *
	 * @return static
	 */
	public function addLimit( int $limit ): static {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Get the limit.
	 *
	 * @return int|null
	 */
	public function getLimit(): ?int {
		return $this->limit;
	}

	/**
	 * Add ordering to the query.
	 *
	 * @param string    $field
	 * @param OrderType $order
	 *
	 * @return static
	 */
	public function addOrder( string $field, OrderType $order ): static {
		$this->ordering->add( $field, $order );

		return $this;
	}

	/**
	 * Get the ordering instructions.
	 *
	 * @return Kmap<string, OrderType>
	 */
	public function getOrdering(): Kmap {
		return $this->ordering;
	}
}

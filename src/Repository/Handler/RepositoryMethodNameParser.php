<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Collection\Klist;
use Axpecto\Storage\Criteria\LogicOperator;
use Axpecto\Storage\Criteria\Operator;
use Exception;

/**
 * Class RepositoryMethodNameParser
 *
 * Parses repository method names into tokenized conditions.
 *
 * For example, the method name "findByUserNameAndAgeGreaterThan" will be parsed into:
 * - A first token with field "userName", operator EQUALS and logic operator AND.
 * - A second token with field "age", operator GREATER_THAN and logic operator AND.
 *
 * @package Axpecto\Repository\Handler
 */
class RepositoryMethodNameParser {

	/**
	 * Parses a repository method name and returns a list of tokenized conditions.
	 *
	 * @param string $methodName Example: "findByUserNameAndAgeGreaterThan"
	 *
	 * @return Klist<ParsedMethodPart>
	 * @throws Exception if no valid prefix is found.
	 */
	public function parse( string $methodName ): Klist {
		// Retrieve the prefix from the list of available prefixes.
		$prefix = Prefix::getList()
		                ->filter( fn( Prefix $p ) => str_starts_with( $methodName, $p->value ) )
		                ->firstOrNull();

		if ( ! $prefix ) {
			throw new Exception( "Method name '$methodName' does not start with a valid prefix." );
		}

		// Remove the prefix from the method name.
		$withoutPrefix = substr( $methodName, strlen( $prefix->value ) );

		// Build a regex pattern that uses all the logic operator values.
		$logicOperatorValues = LogicOperator::getList()
		                                    ->map( fn( LogicOperator $op ) => preg_quote( $op->value, '/' ) )
		                                    ->join( '|' );

		// Split the remaining method name into parts, ensuring that logical operators are included
		// as the beginning of each subsequent token.
		$parts = preg_split( '/(?=(' . $logicOperatorValues . '))/', $withoutPrefix, - 1, PREG_SPLIT_NO_EMPTY );

		return listFrom( $parts )
			->map( fn( string $part ) => $this->parsePart( $part, $prefix ) );
	}

	/**
	 * Parses an individual condition string (e.g., "AgeGreaterThan") into its field name and operator.
	 *
	 * @param string $conditionStr The condition part of the method name.
	 * @param Prefix $prefix       The repository prefix.
	 *
	 * @return ParsedMethodPart
	 */
	private function parsePart( string $conditionStr, Prefix $prefix ): ParsedMethodPart {
		// Determine the logic operator by checking if the condition string starts with a known operator.
		$logicOperator = LogicOperator::getList()
		                              ->filter( fn( LogicOperator $op ) => str_starts_with( $conditionStr, $op->value ) )
		                              ->firstOrNull() ?? LogicOperator::AND;

		// Determine the comparison operator by checking for known keywords anywhere in the condition.
		$operator = Operator::getList()
		                    ->filter( fn( Operator $op ) => str_contains( $conditionStr, $op->value ) )
		                    ->firstOrNull() ?? Operator::EQUALS;

		// Remove the repository prefix, logic operator prefix, and operator keyword to isolate the field name.
		$fieldName = lcfirst( str_replace( [ $prefix->value, $logicOperator->value, $operator->value ], '', $conditionStr ) );

		return new ParsedMethodPart( $prefix, $logicOperator, $fieldName, $operator );
	}
}

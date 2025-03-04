<?php

use Axpecto\Collection\Klist;
use Axpecto\Repository\Handler\RepositoryMethodNameParser;
use Axpecto\Repository\Handler\Prefix;
use Axpecto\Repository\Handler\ParsedMethodPart;
use Axpecto\Storage\Criteria\LogicOperator;
use Axpecto\Storage\Criteria\Operator;
use PHPUnit\Framework\TestCase;

final class RepositoryMethodNameParserTest extends TestCase {

	public function testParseMethodName(): void {
		$parser     = new RepositoryMethodNameParser();
		$methodName = "findByUserNameAndAgeGreaterThan";

		$result = $parser->parse( $methodName );
		$this->assertInstanceOf( Klist::class, $result );

		$parts = $result->toArray();
		$this->assertCount( 2, $parts );

		/** @var ParsedMethodPart $first */
		$first = $parts[0];
		$this->assertEquals( Prefix::FIND_BY, $first->prefix );
		$this->assertEquals( "userName", $first->field );
		$this->assertEquals( Operator::EQUALS, $first->operator );
		$this->assertEquals( LogicOperator::AND, $first->logicOperator );

		/** @var ParsedMethodPart $second */
		$second = $parts[1];
		$this->assertEquals( Prefix::FIND_BY, $second->prefix );
		$this->assertEquals( "age", $second->field );
		$this->assertEquals( Operator::GREATER_THAN, $second->operator );
		$this->assertEquals( LogicOperator::AND, $second->logicOperator );
	}

	public function testParseMethodNameInvalidPrefix(): void {
		$this->expectException( Exception::class );
		$parser = new RepositoryMethodNameParser();
		$parser->parse( "invalidMethodName" );
	}
}

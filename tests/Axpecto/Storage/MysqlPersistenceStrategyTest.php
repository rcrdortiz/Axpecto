<?php

namespace Axpecto\Storage\Tests;

use Axpecto\Annotation\AnnotationReader;
use Axpecto\Collection\Klist;
use Axpecto\Storage\Connection\Connection;
use Axpecto\Storage\Connection\Pdo\PdoStatement;
use Axpecto\Storage\Criteria\Criteria;
use Axpecto\Storage\Criteria\Operator;
use Axpecto\Storage\Entity\Entity as EntityAttribute;
use Axpecto\Storage\MysqlPersistenceStrategy;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MysqlPersistenceStrategyTest extends TestCase {
	private Connection&MockObject $conn;
	private AnnotationReader&MockObject $reader;
	private MysqlPersistenceStrategy $strategy;

	/**
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		$this->conn     = $this->createMock( Connection::class );
		$this->reader   = $this->createMock( AnnotationReader::class );
		$this->strategy = new MysqlPersistenceStrategy( $this->conn, $this->reader );
	}

	public function testGetEntityMetadataThrowsWhenNoAnnotation(): void {
		$this->reader
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->with( FakeEntity::class, EntityAttribute::class )
			->willReturn( emptyList() );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( "Entity annotation missing on class " . FakeEntity::class );
		// invoke via save() so that getEntityMetadata is called:
		$this->strategy->save( new FakeEntity() );
	}

	/**
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSaveDoesInsertWhenNoId(): void {
		$entity      = new FakeEntity();
		$entity->foo = 'bar';
		// id starts null
		$entity->id = null;

		$attr = new EntityAttribute(
			storage: AnyStorage::class,
			table: 'my_table',
			idField: 'id',
		);

		$this->reader
			->method( 'getClassAnnotations' )
			->willReturn( listOf( $attr ) );

		$stmt = $this->createMock( PDOStatement::class );
		$stmt->expects( $this->once() )
		     ->method( 'execute' )
		     ->with( [ null, 'bar' ] )  // <-- swapped order here
		     ->willReturn( true );

		$this->conn
			->expects( $this->once() )
			->method( 'prepare' )
			->with( "INSERT INTO my_table (id, foo) VALUES (?, ?)" )
			->willReturn( $stmt );

		$this->conn
			->method( 'lastInsertId' )
			->willReturn( '42' );

		$result = $this->strategy->save( $entity );

		$this->assertTrue( $result );
		$this->assertSame( 42, $entity->id );
	}

	public function testSaveDoesUpdateWhenIdPresent(): void {
		$entity      = new FakeEntity();
		$entity->foo = 'baz';
		$entity->id  = 99;

		$attr = new EntityAttribute(
			storage: AnyStorage::class,
			table: 'other',
			idField: 'id',
		);

		$this->reader
			->method( 'getClassAnnotations' )
			->willReturn( listOf( $attr ) );

		$stmt = $this->createMock( PDOStatement::class );
		$stmt->expects( $this->once() )
		     ->method( 'execute' )
		     ->with( [ 'baz', 99 ] )
		     ->willReturn( true );
		$this->conn
			->expects( $this->once() )
			->method( 'prepare' )
			->with( "UPDATE other SET foo = ? WHERE id = ?" )
			->willReturn( $stmt );

		$this->assertTrue( $this->strategy->save( $entity ) );
	}

	/**
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws Exception
	 */
	public function testFindAllByCriteriaBuildsCorrectSqlAndParams(): void {
		$criteria    = new Criteria()
			->addCondition( 'a', 1, Operator::EQUALS )
			->addCondition( 'b', [ 2, 3 ], Operator::BETWEEN, null )
			->addCondition( 'c', [ 'x', 'y' ], Operator::IN );
		$entityClass = FakeEntity::class;

		$attr = new EntityAttribute(
			storage: AnyStorage::class,
			table: 'tbl',
			idField: 'id',
		);

		$this->reader
			->method( 'getClassAnnotations' )
			->willReturn( listOf( $attr ) );

		$stmt = $this->createMock( PDOStatement::class );
		// capture the SQL and params
		$this->conn
			->expects( $this->once() )
			->method( 'prepare' )
			->with( $this->stringContains( 'SELECT * FROM tbl WHERE a = ? AND b BETWEEN ? AND ? AND c IN (?, ?)' ) )
			->willReturn( $stmt );

		$stmt->expects( $this->once() )
		     ->method( 'execute' )
		     ->with( [ 1, 2, 3, 'x', 'y' ] );

		$stmt->method( 'fetchAll' )
		     ->willReturn( [ [ 'foo' => 123 ] ] );

		$out = $this->strategy->findAllByCriteria( $criteria, $entityClass );
		$this->assertInstanceOf( Klist::class, $out );
		$this->assertSame( [ [ 'foo' => 123 ] ], $out->toArray() );
	}

	public function testFindOneByCriteria(): void {
		$criteria    = new Criteria();
		$entityClass = FakeEntity::class;

		// Prepare two dummy objects
		$first  = new \stdClass;
		$second = new \stdClass;

		$spy = $this->getMockBuilder( MysqlPersistenceStrategy::class )
		            ->setConstructorArgs( [ $this->conn, $this->reader ] )
		            ->onlyMethods( [ 'findAllByCriteria' ] )
		            ->getMock();

		$spy->expects( $this->once() )
		    ->method( 'findAllByCriteria' )
		    ->with( $criteria, $entityClass )
		    ->willReturn( listOf( $first, $second ) );

		$this->assertSame( $first, $spy->findOneByCriteria( $criteria, $entityClass ) );
	}

	/**
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testDeleteBuildsCorrectSql(): void {
		$attr = new EntityAttribute(
			storage: AnyStorage::class,
			table: 't',
			idField: 'pk',
		);

		$this->reader
			->method( 'getClassAnnotations' )
			->willReturn( listOf( $attr ) );

		$stmt = $this->createMock( PDOStatement::class );
		$stmt->expects( $this->once() )
		     ->method( 'execute' )
		     ->with( [ 55 ] )
		     ->willReturn( true );

		$this->conn
			->expects( $this->once() )
			->method( 'prepare' )
			->with( "DELETE FROM t WHERE pk = ?" )
			->willReturn( $stmt );

		$this->assertTrue( $this->strategy->delete( 55, FakeEntity::class ) );
	}

	/**
	 * @dataProvider operatorProvider
	 */
	public function testMapOperator( Operator $op, string $expected ): void {
		$r = new \ReflectionMethod( MysqlPersistenceStrategy::class, 'mapOperator' );
		$r->setAccessible( true );
		$this->assertSame( $expected, $r->invoke( $this->strategy, $op ) );
	}

	public static function operatorProvider(): array {
		return [
			[ Operator::GREATER_THAN_EQUAL, '>=' ],
			[ Operator::GREATER_THAN, '>' ],
			[ Operator::AFTER, '>' ],
			[ Operator::LESS_THAN_EQUAL, '<=' ],
			[ Operator::LESS_THAN, '<' ],
			[ Operator::BEFORE, '<' ],
			[ Operator::BETWEEN, 'BETWEEN' ],
			[ Operator::IN, 'IN' ],
			[ Operator::NOT_IN, 'NOT IN' ],
			[ Operator::IS_NULL, 'IS NULL' ],
			[ Operator::IS_NOT_NULL, 'IS NOT NULL' ],
			[ Operator::LIKE, 'LIKE' ],
			[ Operator::NOT_LIKE, 'NOT LIKE' ],
			[ Operator::CONTAINS, 'LIKE' ],
			[ Operator::STARTING_WITH, 'LIKE' ],
			[ Operator::ENDING_WITH, 'LIKE' ],
			[ Operator::EQUALS, '=' ],
		];
	}
}

/**
 * A fake entity class used only for these tests.
 */
class FakeEntity {
	public ?int $id = null;
	public string $foo;
}

class AnyStorage {
}
// Dummy class to satisfy the EntityAttribute storage parameter.}

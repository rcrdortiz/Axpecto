<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Repository\Repository;
use Axpecto\Storage\Criteria\LogicOperator;
use Axpecto\Storage\Criteria\Operator;
use Axpecto\Storage\Entity\Entity;
use Axpecto\Storage\Entity\EntityField;
use Axpecto\Storage\Entity\EntityMetadataService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RepositoryBuildHandlerTest extends TestCase {

	public function testInterceptSkipsIfInvalidAnnotation(): void {
		$reflect  = $this->createMock( ReflectionUtils::class );
		$parser   = $this->createMock( RepositoryMethodNameParser::class );
		$metadata = $this->createMock( EntityMetadataService::class );

		$handler = new RepositoryBuildHandler( $reflect, $parser, $metadata );

		$this->expectException( \InvalidArgumentException::class );

		$annotation = $this->createMock( Annotation::class );
		$context    = $this->createMock( BuildContext::class );

		$handler->intercept( $annotation, $context );
	}

	public function testInterceptGeneratesMethod(): void {
		$reflect  = $this->createMock( ReflectionUtils::class );
		$parser   = $this->createMock( RepositoryMethodNameParser::class );
		$metadata = $this->createMock( EntityMetadataService::class );

		$handler = new RepositoryBuildHandler( $reflect, $parser, $metadata );

		// Mocks
		$repositoryAnnotation = new Repository( entityClass: DummyEntity::class );
		$repositoryAnnotation->setAnnotatedClass( DummyRepository::class );

		$entityAnnotation = new Entity( storage: DummyStorage::class, table: 'dummy' );

		$reflect->method( 'getClassAnnotations' )
		        ->willReturn( listOf( $entityAnnotation ) );

		$method = new ReflectionMethod( DummyRepository::class, 'findByIdAndName' );

		$reflect->method( 'getAbstractMethods' )
		        ->willReturn( listOf( $method ) );

		$reflect->method( 'getMethodDefinitionString' )
		        ->willReturn( 'public function findByIdAndName($id, $name)' );

		$parser->method( 'parse' )->willReturn(
			listOf(
				new ParsedMethodPart( Prefix::GET_BY, LogicOperator::AND, 'id', Operator::EQUALS ),
				new ParsedMethodPart( Prefix::GET_BY, LogicOperator::AND, 'name', Operator::EQUALS )
			)
		);

		$metadata->method( 'getFields' )->willReturn(
			listOf(
				new EntityField( 'id', 'string', false, DummyEntity::class, persistenceMapping: 'id' ),
				new EntityField( 'name', 'string', false, DummyEntity::class, persistenceMapping: 'name_mapping' ),
			)
		);

		$context = new BuildContext( DummyRepository::class );

		$handler->intercept( $repositoryAnnotation, $context );

		$this->assertTrue( $context->methods->offsetExists( 'findByIdAndName' ) );
		$this->assertStringContainsString( 'addCondition', $context->methods['findByIdAndName'] );
		$this->assertStringContainsString( 'name_mapping', $context->methods['findByIdAndName'] );
	}
}

// Dummy classes for the test

interface DummyRepository {
	public function findByIdAndName( $id, $name );
}

class DummyEntity {
	public function __construct(
		public int $id,
		public string $name
	) {
	}
}

class DummyStorage {
}

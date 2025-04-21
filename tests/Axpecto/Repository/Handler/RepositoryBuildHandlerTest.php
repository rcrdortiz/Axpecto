<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationReader;
use Axpecto\ClassBuilder\BuildOutput;
use Axpecto\Code\MethodCodeGenerator;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Repository\Mapper\ArrayToEntityMapper;
use Axpecto\Repository\Repository;
use Axpecto\Storage\Criteria\LogicOperator;
use Axpecto\Storage\Criteria\Operator;
use Axpecto\Storage\Entity\Entity;
use Axpecto\Storage\Entity\EntityField;
use Axpecto\Storage\Entity\EntityMetadataService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RepositoryBuildHandlerTest extends TestCase {
	private ReflectionUtils $reflect;
	private MethodCodeGenerator $codeGen;
	private RepositoryMethodNameParser $parser;
	private EntityMetadataService $metadata;
	private RepositoryBuildHandler $handler;
	private AnnotationReader $annotationReader;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void {
		$this->reflect          = $this->createMock( ReflectionUtils::class );
		$this->codeGen          = $this->createMock( MethodCodeGenerator::class );
		$this->parser           = $this->createMock( RepositoryMethodNameParser::class );
		$this->metadata         = $this->createMock( EntityMetadataService::class );
		$this->annotationReader = $this->createMock( AnnotationReader::class );

		$this->handler = new RepositoryBuildHandler(
			$this->reflect,
			$this->codeGen,
			$this->parser,
			$this->metadata,
			$this->annotationReader,
		);
	}

	public function testInterceptSkipsIfInvalidAnnotation(): void {
		$this->expectException( InvalidArgumentException::class );

		$annotation = $this->createMock( Annotation::class );
		$context    = $this->createMock( BuildOutput::class );

		$this->handler->intercept( $annotation, $context );
	}

	public function testInterceptGeneratesMethod(): void {
		// 1) Prepare a valid @Repository annotation
		$repositoryAnnotation = new Repository( entityClass: DummyEntity::class );
		$repositoryAnnotation->setAnnotatedClass( DummyRepository::class );

		// 2) Stub fetching the Entity metadata
		$entityAnnotation = new Entity( storage: DummyStorage::class, table: 'dummy' );
		$this->annotationReader
			->method( 'getClassAnnotations' )
			->willReturn( listOf( $entityAnnotation ) );

		// 3) Stub the abstract methods on the repository interface
		$method = new ReflectionMethod( DummyRepository::class, 'findByIdAndName' );
		$this->reflect
			->method( 'getAbstractMethods' )
			->willReturn( listOf( $method ) );

		// 4) Stub the name parser to produce two conditions (id, name)
		$this->parser
			->method( 'parse' )
			->with( 'findByIdAndName' )
			->willReturn( listOf(
				new ParsedMethodPart( prefix: Prefix::FIND_BY, logicOperator: LogicOperator::AND, field: 'id', operator: Operator::EQUALS ),
				new ParsedMethodPart( prefix: Prefix::FIND_BY, logicOperator: LogicOperator::AND, field: 'name', operator: Operator::EQUALS )
			) );

		// 5) Stub the metadata service to map fields to storage columns
		$this->metadata
			->method( 'getFields' )
			->with( DummyEntity::class )
			->willReturn( listOf(
				new EntityField( name: 'id', type: 'int', nullable: false, entityClass: DummyEntity::class, default: false, persistenceMapping: 'id_field' ),
				new EntityField( name: 'name', type: 'string', nullable: false, entityClass: DummyEntity::class, default: false, persistenceMapping: 'name_mapping' )
			) );

		// 6) Stub the code generator to return a dummy signature
		$this->codeGen
			->expects( $this->once() )
			->method( 'implementMethodSignature' )
			->with( DummyRepository::class, 'findByIdAndName' )
			->willReturn( 'public function findByIdAndName($id, $name)' );

		// 7) Create a fresh BuildOutput and run intercept()
		$context = new BuildOutput( DummyRepository::class );
		$this->handler->intercept( $repositoryAnnotation, $context );

		// 8) Assertions:

		//   a) Dependencies were injected
		$this->assertTrue( $context->properties->offsetExists( ArrayToEntityMapper::class ) );
		$this->assertTrue( $context->properties->offsetExists( DummyStorage::class ) );

		//   b) Method was added
		$this->assertTrue( $context->methods->offsetExists( 'findByIdAndName' ) );

		//   c) Generated body contains both column names and addCondition call
		$body = $context->methods['findByIdAndName'];
		$this->assertStringContainsString( 'addCondition', $body );
		$this->assertStringContainsString( 'id_field', $body );
		$this->assertStringContainsString( 'name_mapping', $body );
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

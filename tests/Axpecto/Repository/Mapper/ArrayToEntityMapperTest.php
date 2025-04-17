<?php

use Axpecto\Collection\Klist;
use Axpecto\Repository\Mapper\ArrayToEntityMapper;
use Axpecto\Storage\Entity\EntityField;
use Axpecto\Storage\Entity\EntityMetadataService;
use PHPUnit\Framework\TestCase;

/**
 * Dummy entity class for testing.
 */
class DummyEntity {
	public function __construct(
		public int $id,
		public string $name
	) {
	}
}

/**
 * Unit test for ArrayToEntityMapper.
 */
class ArrayToEntityMapperTest extends TestCase {

	public function testMap(): void {
		// Create two EntityField definitions simulating metadata extracted by EntityMetadataService
		$fieldId = new EntityField(
			name:               'id',
			type:               'int',
			nullable:           false,
			entityClass:        DummyEntity::class,
			default:            EntityField::NO_DEFAULT_VALUE_SPECIFIED,
			persistenceMapping: 'dummy_field'
		);

		$fieldName = new EntityField(
			name:               'name',
			type:               'string',
			nullable:           false,
			entityClass:        DummyEntity::class,
			default:            EntityField::NO_DEFAULT_VALUE_SPECIFIED,
			persistenceMapping: 'name' // default mapping matches property name
		);

		// Create a mock for EntityMetadataService
		$metadataService = $this->createMock( EntityMetadataService::class );

		// Expect getFields to return a Klist of EntityField objects
		$metadataService->expects( $this->once() )
		                ->method( 'getFields' )
		                ->with( DummyEntity::class )
		                ->willReturn( new Klist( [ $fieldId, $fieldName ] ) );

		// Instantiate the mapper
		$mapper = new ArrayToEntityMapper( $metadataService );

		// Input data array using database field names
		$data = [
			'dummy_field' => 123,
			'name'        => 'John Doe',
		];

		// Map the data to a DummyEntity instance
		$entity = $mapper->mapEntityFromArray( DummyEntity::class, $data );

		// Assertions
		$this->assertInstanceOf( DummyEntity::class, $entity );
		$this->assertEquals( 123, $entity->id );
		$this->assertEquals( 'John Doe', $entity->name );
	}
}

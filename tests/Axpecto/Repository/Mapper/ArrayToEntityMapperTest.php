<?php

use Axpecto\Collection\Klist;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Repository\Mapper\ArrayToEntityMapper;
use Axpecto\Storage\Criteria\Mapping;
use PHPUnit\Framework\TestCase;

/**
 * Dummy entity class for testing.
 */
class DummyEntity {
	/**
	 * @param int    $id Mapped from 'dummy_field'
	 * @param string $name
	 */
	public function __construct(
		#[Mapping( 'dummy_field' )]
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
		// Prepare a Klist of constructor arguments.
		$constructorArguments = new Klist( [
			                                   new Argument( 'id', 'int', null ),
			                                   new Argument( 'name', 'string', null ),
		                                   ] );

		// Prepare Klist for the Mapping annotation:
		// For 'id', we want to map to 'dummy_field'.
		$mappingForId = new Klist( [
			                           new Mapping( 'dummy_field' ),
		                           ] );
		// For 'name', assume no Mapping annotation.
		$emptyMapping = new Klist( [] );

		// Create a mock ReflectionUtils.
		$reflectionUtils = $this->createMock( ReflectionUtils::class );

		// Stub getConstructorArguments() to return our constructor arguments.
		$reflectionUtils->expects( $this->once() )
		                ->method( 'getConstructorArguments' )
		                ->with( DummyEntity::class )
		                ->willReturn( $constructorArguments );

		// Stub getParamAnnotations() to return the mapping for 'id' and empty for 'name'.
		$reflectionUtils->method( 'getParamAnnotations' )
		                ->willReturnCallback( function ( $entityClass, $methodName, $paramName, $annotationClass ) use ( $mappingForId, $emptyMapping ) {
			                if ( $entityClass === DummyEntity::class && $methodName === '__construct' && $paramName === 'id' && $annotationClass === Mapping::class ) {
				                return $mappingForId;
			                }

			                return $emptyMapping;
		                } );

		// Instantiate the mapper.
		$mapper = new ArrayToEntityMapper( $reflectionUtils );

		// Data array using the database field name for the id.
		$data = [
			'dummy_field' => 123,
			'name'        => 'John Doe',
		];

		// Map the data to a DummyEntity instance.
		$entity = $mapper->map( DummyEntity::class, $data );

		// Assert that the returned entity is an instance of DummyEntity with the expected values.
		$this->assertInstanceOf( DummyEntity::class, $entity );
		$this->assertEquals( 123, $entity->id );
		$this->assertEquals( 'John Doe', $entity->name );
	}
}

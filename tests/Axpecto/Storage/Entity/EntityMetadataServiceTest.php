<?php declare( strict_types=1 );

namespace Axpecto\Storage\Entity\Tests;

use Axpecto\Annotation\AnnotationReader;
use Axpecto\Collection\Klist;
use Axpecto\Reflection\Dto\Argument;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Storage\Entity\Column\Column;
use Axpecto\Storage\Entity\Entity as EntityAttribute;
use Axpecto\Storage\Entity\EntityField;
use Axpecto\Storage\Entity\EntityMetadataService;
use PHPUnit\Framework\TestCase;
use Exception;

class EntityMetadataServiceTest extends TestCase {
	private ReflectionUtils $reflect;
	private AnnotationReader $reader;
	private EntityMetadataService $svc;

	protected function setUp(): void {
		$this->reflect = $this->createMock( ReflectionUtils::class );
		$this->reader  = $this->createMock( AnnotationReader::class );
		$this->svc     = new EntityMetadataService( $this->reflect, $this->reader );
	}

	/**
	 * @throws \ReflectionException
	 */
	public function testGetFieldsWithAndWithoutColumnOverrides(): void {
		$entityClass = DummyEntity::class;

		// Simulate two constructor arguments: foo and bar
		$argFoo = new Argument( name: 'foo', type: 'string', nullable: false, default: null );
		$argBar = new Argument( name: 'bar', type: 'int', nullable: true, default: 42 );

		$this->reflect
			->expects( $this->once() )
			->method( 'getConstructorArguments' )
			->with( $entityClass )
			->willReturn( listOf( $argFoo, $argBar ) );

		// Prepare a Column override for "foo"
		$column = new Column(
			name: 'col_foo',
			isPrimary: true,
			isUnique: true,
			isIndexed: true,
			autoIncrement: true,
			isNullable: true,
			type: 'custom_type',
			defaultValue: 'def',
			onUpdate: 'now()'
		);

		// Stub getParameterAnnotations: foo→[$column], bar→[]
		$this->reader
			->expects( $this->exactly( 2 ) )
			->method( 'getParameterAnnotations' )
			->willReturnCallback( function (
				string $cls,
				string $method,
				string $paramName,
				string $annotationClass
			) use ( $column ): Klist {
				// only foo gets the override
				return $paramName === 'foo'
					? listOf( $column )
					: emptyList();
			} );

		$fields = $this->svc->getFields( $entityClass );
		$arr    = $fields->toArray();

		// "foo" should reflect the Column override
		/** @var EntityField $fFoo */
		$fFoo = $arr[0];
		$this->assertSame( 'foo', $fFoo->name );
		$this->assertSame( 'custom_type', $fFoo->type );
		$this->assertTrue( $fFoo->nullable );
		$this->assertSame( 'def', $fFoo->default );
		$this->assertSame( 'col_foo', $fFoo->persistenceMapping );
		$this->assertTrue( $fFoo->isAutoIncrement );
		$this->assertTrue( $fFoo->isPrimary );
		$this->assertTrue( $fFoo->isUnique );
		$this->assertTrue( $fFoo->isIndexed );
		$this->assertSame( 'now()', $fFoo->onUpdate );

		// "bar" should fall back to the Argument defaults
		/** @var EntityField $fBar */
		$fBar = $arr[1];
		$this->assertSame( 'bar', $fBar->name );
		$this->assertSame( 'int', $fBar->type );
		$this->assertTrue( $fBar->nullable );
		$this->assertEquals( 42, $fBar->default );
		$this->assertSame( 'bar', $fBar->persistenceMapping );
		$this->assertFalse( $fBar->isAutoIncrement );
		$this->assertFalse( $fBar->isPrimary );
		$this->assertFalse( $fBar->isUnique );
		$this->assertFalse( $fBar->isIndexed );
		$this->assertNull( $fBar->onUpdate );
	}

	public function testGetEntityReturnsAnnotation(): void {
		$entityClass = DummyEntity::class;
		$entityAnno  = new EntityAttribute( storage: DummyStorage::class, table: 'tbl' );

		$this->reader
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->with( $entityClass, EntityAttribute::class )
			->willReturn( listOf( $entityAnno ) );

		$this->assertSame( $entityAnno, $this->svc->getEntity( $entityClass ) );
	}

	public function testGetEntityMissingThrows(): void {
		$entityClass = DummyEntity::class;

		$this->reader
			->expects( $this->once() )
			->method( 'getClassAnnotations' )
			->with( $entityClass, EntityAttribute::class )
			->willReturn( emptyList() );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( "Entity annotation missing on class $entityClass" );

		$this->svc->getEntity( $entityClass );
	}
}

// Dummy types for test

class DummyEntity {
	public function __construct( string $foo, ?int $bar = 42 ) {
	}
}

class DummyStorage {
}

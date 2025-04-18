<?php

namespace Axpecto\Storage\EntitySchemaGenerator\MySql;

use Axpecto\Storage\Connection\Connection;
use Axpecto\Storage\Entity\EntityMetadataService;
use Exception;

class MySqlEntitySchemaGenerator {
	public function __construct(
		private readonly Connection $connection,
		private readonly EntityMetadataService $metadataService,
		private readonly EntityFieldMapper $fieldMapper,
	) {
	}

	public function create( string $entityClass ): void {
		$entity = $this->metadataService->getEntity( $entityClass );

		if ( $this->isTableCreated( $entity->table ) ) {
			return;
		}

		$fields = $this->metadataService
			->getFields( $entityClass )
			->map( $this->fieldMapper->mapToSqlCreateField( ... ) )
			->join( ',' );

		$createSql = "CREATE TABLE `{$entity->table}` ($fields) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

		$createStmt = $this->connection->prepare( $createSql );
		$createStmt->execute();

		if ( $createStmt->getLastError() ) {
			throw new Exception( 'Error creating table: ' . $createStmt->getLastError() . print_r( $createSql, true ) );
		}
	}

	private function isTableCreated( $tableName ) {
		$sql  = "SHOW TABLES LIKE '$tableName'";
		$stmt = $this->connection->prepare( $sql );
		$stmt->execute();

		return $stmt->rowCount() > 0;
	}

	public function destroy( string $entityClass ) {
		$entity = $this->metadataService->getEntity( $entityClass );

		$stmt = $this->connection->prepare( "DROP TABLE IF EXISTS {$entity->table}" );
		$stmt->execute();
	}
}
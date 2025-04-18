<?php

namespace Axpecto\Storage\Connection\Pdo;

use Axpecto\Storage\Connection\Connection;
use Axpecto\Storage\Connection\Statement;
use Override;
use PDO;

/**
 * @psalm-suppress UnusedClass Used via annotation build system / DI container
 */
class PdoConnection implements Connection {
	public function __construct( private readonly PDO $pdo ) {
	}

	#[Override]
	public function prepare( string $sql ): Statement {
		$stmt = $this->pdo->prepare( $sql );

		return new PdoStatement( $stmt );
	}

	#[Override]
	public function lastInsertId(): string {
		return $this->pdo->lastInsertId();
	}
}
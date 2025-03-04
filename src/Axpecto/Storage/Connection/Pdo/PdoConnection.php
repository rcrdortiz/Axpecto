<?php

namespace Axpecto\Storage\Connection\Pdo;

use Axpecto\Storage\Connection\Connection;
use Axpecto\Storage\Connection\Statement;
use PDO;

class PdoConnection implements Connection {
	public function __construct( private readonly PDO $pdo ) {
	}

	public function prepare( string $sql ): Statement {
		$stmt = $this->pdo->prepare( $sql );

		return new PdoStatement( $stmt );
	}

	public function lastInsertId(): string {
		return $this->pdo->lastInsertId();
	}
}
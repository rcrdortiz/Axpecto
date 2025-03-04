<?php

namespace Axpecto\Storage\Connection\Pdo;

use Axpecto\Storage\Connection\Statement;

class PdoStatement implements Statement {
	public function __construct( private readonly \PDOStatement $stmt ) {
	}

	public function execute( array $params = [] ): bool {
		return $this->stmt->execute( $params );
	}

	public function fetchAll(): array {
		return $this->stmt->fetchAll();
	}
}
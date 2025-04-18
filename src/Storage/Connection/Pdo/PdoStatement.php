<?php

namespace Axpecto\Storage\Connection\Pdo;

use Axpecto\Storage\Connection\Statement;
use PDOStatement as CorePDOStatement;

class PdoStatement implements Statement {
	public function __construct( private readonly CorePDOStatement $stmt ) {
	}

	public function execute( array $params = [] ): bool {
		return $this->stmt->execute( $params );
	}

	public function fetchAll(): array {
		return $this->stmt->fetchAll();
	}

	public function getLastError(): string {
		return json_encode( $this->stmt->errorInfo() ) ?: '';
	}

	public function rowCount(): int {
		return $this->stmt->rowCount();
	}
}
<?php

namespace Axpecto\Storage\Connection\Pdo;

use Axpecto\Storage\Connection\Statement;
use Override;
use PDOStatement as CorePDOStatement;

class PdoStatement implements Statement {
	public function __construct( private readonly CorePDOStatement $stmt ) {
	}

	#[Override]
	public function execute( array $params = [] ): bool {
		return $this->stmt->execute( $params );
	}

	#[Override]
	public function fetchAll(): array {
		return $this->stmt->fetchAll();
	}

	#[Override]
	public function getLastError(): string {
		return json_encode( $this->stmt->errorInfo() ) ?: '';
	}

	#[Override]
	public function rowCount(): int {
		return $this->stmt->rowCount();
	}
}
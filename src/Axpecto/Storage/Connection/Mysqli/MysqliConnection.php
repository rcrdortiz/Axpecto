<?php

namespace Axpecto\Storage\Connection\Mysqli;

use Axpecto\Storage\Connection\Connection;
use Axpecto\Storage\Connection\Statement;
use Exception;
use mysqli;

class MysqliConnection implements Connection {
	public function __construct( private readonly mysqli $mysqli ) {
	}

	/**
	 * @throws Exception
	 */
	public function prepare( string $sql ): Statement {
		$stmt = $this->mysqli->prepare( $sql );
		if ( ! $stmt ) {
			throw new Exception( "Mysqli prepare failed: " . $this->mysqli->error );
		}

		return new MysqliStatement( $stmt );
	}

	public function lastInsertId(): string {
		return (string) $this->mysqli->insert_id;
	}
}

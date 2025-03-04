<?php

namespace Axpecto\Storage\Connection\Mysqli;

use Axpecto\Storage\Connection\Statement;
use Exception;
use mysqli_stmt;

class MysqliStatement implements Statement {
	public function __construct( private readonly mysqli_stmt $stmt ) {
	}

	/**
	 * Executes the prepared statement with the given parameters.
	 *
	 * @param array $params
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function execute( array $params = [] ): bool {
		if ( ! empty( $params ) ) {
			$types = $this->getParamTypes( $params );
			// Prepare an array of references.
			$bindParams   = [];
			$bindParams[] = $types;
			foreach ( $params as $key => $value ) {
				$bindParams[] = &$value;
			}
			if ( ! call_user_func_array( [ $this->stmt, 'bind_param' ], $bindParams ) ) {
				throw new Exception( "Mysqli bind_param failed: " . $this->stmt->error );
			}
		}

		return $this->stmt->execute();
	}

	public function fetchAll(): array {
		$result = $this->stmt->get_result();
		if ( $result === false ) {
			return [];
		}
		$rows = $result->fetch_all( MYSQLI_ASSOC );
		$this->stmt->close();

		return $rows;
	}

	/**
	 * Determine parameter types for MySQLi binding.
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	private function getParamTypes( array $params ): string {
		$types = '';
		foreach ( $params as $param ) {
			if ( is_int( $param ) ) {
				$types .= 'i';
			} elseif ( is_float( $param ) ) {
				$types .= 'd';
			} else {
				$types .= 's';
			}
		}

		return $types;
	}
}
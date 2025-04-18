<?php

namespace Axpecto\Storage\Connection;

interface Connection {
	/**
	 * Prepares a SQL statement.
	 *
	 * @param string $sql
	 *
	 * @return Statement
	 */
	public function prepare( string $sql ): Statement;

	/**
	 * Returns the ID of the last inserted row.
	 *
	 * @return string
	 */
	public function lastInsertId(): string;
}
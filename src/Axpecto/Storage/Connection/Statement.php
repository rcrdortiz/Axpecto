<?php

namespace Axpecto\Storage\Connection;

interface Statement {
	/**
	 * Executes the prepared statement with the given parameters.
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function execute( array $params = [] ): bool;

	/**
	 * Fetches all result rows as an associative array.
	 *
	 * @return array
	 */
	public function fetchAll(): array;
}
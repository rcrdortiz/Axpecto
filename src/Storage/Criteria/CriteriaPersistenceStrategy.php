<?php

namespace Axpecto\Storage\Criteria;

use Axpecto\Collection\Klist;

interface CriteriaPersistenceStrategy {
	/**
	 * @psalm-suppress PossiblyUnusedMethod Used by generated code and/or clients.
	 *
	 * @param object $entity
	 *
	 * @return bool
	 */
	public function save( object $entity ): bool;

	/**
	 * @psalm-suppress PossiblyUnusedMethod Used by generated code and/or clients.
	 *
	 * @param Criteria $criteria
	 * @param string   $entityClass
	 *
	 * @return Klist
	 */
	public function findAllByCriteria( Criteria $criteria, string $entityClass ): Klist;

	/**
	 * @psalm-suppress PossiblyUnusedMethod Used by generated code and/or clients.
	 * @template T
	 *
	 * @param Criteria $criteria
	 * @param class-string<T> $entityClass
	 *
	 * @return T|null
	 */
	public function findOneByCriteria(Criteria $criteria, string $entityClass);

	/**
	 * @psalm-suppress PossiblyUnusedMethod Used by generated code and/or clients.
	 *
	 * @param int    $id
	 * @param string $entityClass
	 */
	public function delete( int $id, string $entityClass );
}
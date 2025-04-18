<?php

namespace Axpecto\Storage\Criteria;

use Axpecto\Collection\Klist;

/**
 * @psalm-suppress PossiblyUnusedMethod Used by generated code and clients.
 */
interface CriteriaPersistenceStrategy {
	/**
	 * @param object $entity
	 *
	 * @return bool
	 */
	public function save( object $entity ): bool;

	/**
	 * @param Criteria $criteria
	 * @param string   $entityClass
	 *
	 * @return Klist
	 */
	public function findAllByCriteria( Criteria $criteria, string $entityClass ): Klist;

	/**
	 * @template T
	 * @param Criteria $criteria
	 * @param class-string<T> $entityClass
	 * @return T|null
	 */
	public function findOneByCriteria(Criteria $criteria, string $entityClass);

	/**
	 * @param int    $id
	 * @param string $entityClass
	 */
	public function delete( int $id, string $entityClass );
}
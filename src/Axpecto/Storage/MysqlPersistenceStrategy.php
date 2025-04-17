<?php

namespace Axpecto\Storage;

use Axpecto\Collection\Klist;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Storage\Connection\Connection;
use Axpecto\Storage\Criteria\Condition;
use Axpecto\Storage\Criteria\Criteria;
use Axpecto\Storage\Criteria\CriteriaPersistenceStrategy;
use Axpecto\Storage\Criteria\Operator;
use Axpecto\Storage\Entity\Entity as EntityAttribute;
use Exception;

class MysqlPersistenceStrategy implements CriteriaPersistenceStrategy {

	public function __construct(
		private readonly Connection $conn,
		private readonly ReflectionUtils $reflect
	) {
	}

	/**
	 * Retrieves entity metadata (Entity attribute) from the given entity class.
	 *
	 * @param string $entityClass
	 *
	 * @return EntityAttribute
	 * @throws Exception
	 */
	private function getEntityMetadata( string $entityClass ): EntityAttribute {
		$entityAnnotation = $this->reflect
			->getClassAnnotations( $entityClass, EntityAttribute::class )
			->firstOrNull();
		if ( ! $entityAnnotation ) {
			throw new Exception( "Entity annotation missing on class $entityClass" );
		}

		return $entityAnnotation;
	}

	/**
	 * Save an entity.
	 *
	 * If the entity contains the primary key field (as defined by the entity metadata),
	 * an update is performed; otherwise, a new record is inserted.
	 *
	 * @param object $entity
	 *
	 * @return bool True on success, false on failure.
	 * @throws Exception
	 */
	public function save( object $entity ): bool {
		$entityClass = get_class( $entity );
		$annotation  = $this->getEntityMetadata( $entityClass );
		$table       = $annotation->table;
		$idField     = $annotation->idField;
		$data        = get_object_vars( $entity );

		if ( isset( $data[ $idField ] ) ) {
			// Update scenario.
			$id = $data[ $idField ];
			unset( $data[ $idField ] );
			$fields    = array_keys( $data );
			$setClause = implode( ', ', array_map( fn( $field ) => "$field = ?", $fields ) );
			$params    = array_values( $data );
			$params[]  = $id;
			$sql       = "UPDATE {$table} SET {$setClause} WHERE {$idField} = ?";
			$stmt      = $this->conn->prepare( $sql );

			return $stmt->execute( $params );
		} else {
			// Insert scenario.
			$fields       = array_keys( $data );
			$columns      = implode( ', ', $fields );
			$placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
			$sql          = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
			$stmt         = $this->conn->prepare( $sql );
			$result       = $stmt->execute( array_values( $data ) );
			if ( $result ) {
				$entity->{$idField} = $this->conn->lastInsertId();
			}

			return $result;
		}
	}

	/**
	 * Retrieve all entities matching the given criteria.
	 *
	 * @TODO Refactor this whole method. Implement Klist reduce.
	 *
	 * @param Criteria $criteria
	 * @param string   $entityClass Fully qualified entity class name.
	 *
	 * @return Klist
	 * @throws Exception
	 */
	public function findAllByCriteria( Criteria $criteria, string $entityClass ): Klist {
		$annotation = $this->getEntityMetadata( $entityClass );
		$table      = $annotation->table;
		$sql        = "SELECT * FROM {$table}";
		$conditions = [];
		$params     = [];

		// Build conditions from the list of Condition objects.
		$first = true;
		/** @var Condition $cond */
		foreach ( $criteria->getConditions()->toArray() as $cond ) {
			$clause = "";
			// If not the first condition, prepend the logic operator.
			if ( ! $first ) {
				$clause .= " " . strtoupper( $cond->logic->value ) . " ";
			}
			// Build condition based on operator.
			switch ( $cond->operator ) {
				case Operator::IS_NULL:
				case Operator::IS_NOT_NULL:
					$clause .= "{$cond->field} " . $this->mapOperator( $cond->operator );
					break;
				case Operator::BETWEEN:
					// Assume $cond->value is an array with exactly two elements.
					$clause   .= "{$cond->field} BETWEEN ? AND ?";
					$params[] = $cond->value[0];
					$params[] = $cond->value[1];
					break;
				case Operator::IN:
				case Operator::NOT_IN:
					// Assume $cond->value is an array.
					if ( ! is_array( $cond->value ) || empty( $cond->value ) ) {
						throw new Exception( "Operator {$cond->operator->value} requires a non-empty array." );
					}
					$placeholders = implode( ', ', array_fill( 0, count( $cond->value ), '?' ) );
					$clause       .= "{$cond->field} " . $this->mapOperator( $cond->operator ) . " (" . $placeholders . ")";
					foreach ( $cond->value as $val ) {
						$params[] = $val;
					}
					break;
				default:
					$clause   .= "{$cond->field} " . $this->mapOperator( $cond->operator ) . " ?";
					$params[] = $cond->value;
					break;
			}
			$conditions[] = $clause;
			$first        = false;
		}

		if ( ! empty( $conditions ) ) {
			$sql .= " WHERE " . implode( "", $conditions );
		}

		// Process ordering.
		$orderingArray = $criteria->getOrdering()->toArray();
		if ( ! empty( $orderingArray ) ) {
			$orderings = [];
			foreach ( $orderingArray as $field => $order ) {
				$orderings[] = "$field " . strtoupper( $order->value );
			}
			$sql .= " ORDER BY " . implode( ", ", $orderings );
		}

		if ( null !== $criteria->getLimit() ) {
			$sql .= " LIMIT " . intval( $criteria->getLimit() );
		}

		$stmt = $this->conn->prepare( $sql );
		$stmt->execute( $params );

		return listFrom( $stmt->fetchAll() );
	}

	/**
	 * Retrieve a single entity matching the given criteria.
	 *
	 * @template T
	 * @param Criteria        $criteria
	 * @param class-string<T> $entityClass Fully qualified entity class name.
	 *
	 * @return T|null Returns an instance of T or null if not found.
	 * @throws Exception
	 */
	public function findOneByCriteria( Criteria $criteria, string $entityClass ): ?object {
		$criteria->addLimit( 1 );
		/** @var T|null $result */
		$result = $this->findAllByCriteria( $criteria, $entityClass )->firstOrNull();

		return $result;
	}

	/**
	 * Delete an entity by its primary key.
	 *
	 * @param int    $id
	 * @param string $entityClass Fully qualified entity class name.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function delete( int $id, string $entityClass ): bool {
		$annotation = $this->getEntityMetadata( $entityClass );
		$table      = $annotation->table;
		$idField    = $annotation->idField;
		$sql        = "DELETE FROM {$table} WHERE {$idField} = ?";
		$stmt       = $this->conn->prepare( $sql );

		return $stmt->execute( [ $id ] );
	}

	/**
	 * Maps an Operator enum to its corresponding SQL operator string.
	 *
	 * @param Operator $operator
	 *
	 * @return string
	 */
	private function mapOperator( Operator $operator ): string {
		return match ( $operator ) {
			Operator::GREATER_THAN_EQUAL => '>=',
			Operator::GREATER_THAN, Operator::AFTER => '>',
			Operator::LESS_THAN_EQUAL => '<=',
			Operator::LESS_THAN, Operator::BEFORE => '<',
			Operator::BETWEEN => 'BETWEEN',
			Operator::NOT_IN => 'NOT IN',
			Operator::IN => 'IN',
			Operator::IS_NOT_NULL => 'IS NOT NULL',
			Operator::IS_NULL => 'IS NULL',
			Operator::NOT_LIKE => 'NOT LIKE',
			Operator::STARTING_WITH, Operator::ENDING_WITH, Operator::CONTAINS, Operator::LIKE => 'LIKE',
			Operator::EQUALS => '=',
		};
	}
}

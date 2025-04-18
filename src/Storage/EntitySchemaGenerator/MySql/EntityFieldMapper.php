<?php

namespace Axpecto\Storage\EntitySchemaGenerator\MySql;

use Axpecto\Storage\Entity\EntityField;

class EntityFieldMapper {

	public function mapToSqlCreateField( EntityField $field ): string {
		$type     = $this->mapPhpTypeToMySql( $field->type );

		$parts = [ "`$field->persistenceMapping`", $type ];

		if ( $field->isAutoIncrement ) {
			$parts[] = "AUTO_INCREMENT";
		}

		if ( $field->isPrimary ) {
			$parts[] = "PRIMARY KEY";
		}

		if ( $field->isUnique && ! $field->isPrimary ) {
			$parts[] = "UNIQUE";
		}

		if ( $field->nullable ) {
			$parts[] = "NULL";
		} else {
			$parts[] = "NOT NULL";
		}

		if ( $field->default !== EntityField::NO_DEFAULT_VALUE_SPECIFIED ) {
			$default = $this->mapDefaultValue( $field->default );
			$parts[] = "DEFAULT $default";
		}

		if ( $field->onUpdate ) {
			$parts[] = "ON UPDATE CURRENT_TIMESTAMP";
		}

		return implode( " ", $parts );
	}

	public function mapDefaultValue( $value ): string {
		// MySQL expressions that should NOT be quoted
		$reservedExpressions = [
			'CURRENT_TIMESTAMP',
			'CURRENT_TIMESTAMP()',
			'NOW()',
			'NULL',
		];

		if (is_int($value) || is_float($value)) {
			return (string) $value;
		}

		if ($value instanceof \DateTimeInterface) {
			return "'" . $value->format('Y-m-d H:i:s') . "'";
		}

		if (is_bool($value)) {
			return $value ? '1' : '0';
		}

		if (is_string($value)) {
			$upper = strtoupper(trim($value));
			if (in_array($upper, $reservedExpressions, true)) {
				return $upper;
			}
			return "'" . addslashes($value) . "'";
		}

		return 'NULL';
	}

	private function mapPhpTypeToMySql( $type ): string {
		switch ( $type ) {
			case 'int':
			case 'integer':
				return 'INT';
			case 'float':
			case 'double':
				return 'FLOAT';
			case 'string':
				return 'VARCHAR(255)';
			case 'bool':
			case 'boolean':
				return 'TINYINT(1)';
			case 'array':
			case 'object':
				return 'JSON';
			case 'DateTime':
				return 'DATETIME';
			default:
				// Fallback: assume a class name or unhandled type
				if ( class_exists( $type ) ) {
					return 'JSON';
				}

				if ( is_string( $type ) ) {
					return $type;
				}

				return 'TEXT'; // Safe fallback
		}
	}
}
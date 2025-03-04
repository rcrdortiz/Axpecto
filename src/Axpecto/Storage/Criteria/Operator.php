<?php

namespace Axpecto\Storage\Criteria;

use Axpecto\Collection\Klist;

enum Operator: string {
	case GREATER_THAN_EQUAL = 'GreaterThanEqual';
	case GREATER_THAN       = 'GreaterThan';
	case LESS_THAN_EQUAL    = 'LessThanEqual';
	case LESS_THAN          = 'LessThan';
	case BETWEEN            = 'Between';
	case NOT_IN             = 'NotIn';
	case IN                 = 'In';
	case IS_NOT_NULL        = 'IsNotNull';
	case IS_NULL            = 'IsNull';
	case NOT_LIKE           = 'NotLike';
	case STARTING_WITH      = 'StartingWith';
	case ENDING_WITH        = 'EndingWith';
	case CONTAINS           = 'Contains';
	case LIKE               = 'Like';
	case BEFORE             = 'Before';
	case AFTER              = 'After';
	case EQUALS             = 'Equals';

	public static function getList() : Klist {
		return listFrom( self::cases() );
	}
}

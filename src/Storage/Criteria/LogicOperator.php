<?php

namespace Axpecto\Storage\Criteria;

use Axpecto\Collection\Klist;

enum LogicOperator: string {
	case AND = 'And';
	case OR = 'Or';

	public static function getList() : Klist {
		return listFrom( self::cases() );
	}
}

<?php

namespace Axpecto\Repository\Handler;

enum Prefix: string {
	case FIND_BY = 'findBy';
	case GET_BY = 'getBy';
	case READ_BY = 'readBy';
	case QUERY_BY = 'queryBy';
	case SEARCH_BY = 'searchBy';
	case GET_ALL = 'getAll';
	case FIND_ALL = 'findAll';
	case READ_ALL = 'readAll';
	case SEARCH_ALL = 'searchAll';
	case QUERY_ALL = 'queryAll';

	public static function getList() {
		return listFrom( self::cases() );
	}
}

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

	public function isAllPrefix(): bool {
		return match ( $this ) {
			self::FIND_BY, self::GET_BY, self::READ_BY, self::QUERY_BY, self::SEARCH_BY => false,
			self::GET_ALL, self::FIND_ALL, self::READ_ALL, self::SEARCH_ALL, self::QUERY_ALL => true,
		};
	}
}

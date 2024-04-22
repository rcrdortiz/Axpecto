<?php

use Axpecto\Collection\Concrete\Klist;
use Axpecto\Collection\Concrete\Kmap;
use Axpecto\Collection\Concrete\MutableKlist;

function listOf( ...$elements ): Klist {
	return new Klist( $elements );
}

function listFrom( array $array ): Klist {
	return new Klist( $array );
}

function emptyList(): Klist {
	return new Klist( [] );
}

function mutable_list_of( ...$elements ): MutableKlist {
	return new MutableKlist( $elements );
}

function mutable_list_from( array $array ): MutableKlist {
	return new MutableKlist( $array );
}

function mutable_empty_list(): MutableKlist {
	return new MutableKlist( [] );
}

function map_of( array $map ): Kmap {
	return new Kmap( $map );
}

function empty_map(): Kmap {
	return new Kmap( [] );
}

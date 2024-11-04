<?php

use Axpecto\Collection\Klist;
use Axpecto\Collection\Kmap;

function listOf( ...$elements ): Klist {
	return new Klist( $elements );
}

function listFrom( array $array ): Klist {
	return new Klist( $array );
}

function emptyList(): Klist {
	return new Klist( [] );
}

function mutableListOf( ...$elements ): Klist {
	return new Klist( $elements, true );
}

function mutableListFrom( array $array ): Klist {
	return new Klist( $array, true );
}

function mutableEmptyList(): Klist {
	return new Klist( [], true );
}

function mapOf( array $map ): Kmap {
	return new Kmap( $map );
}

function emptyMap(): Kmap {
	return new Kmap( [] );
}

function mutableMapOf( array $map ): Kmap {
	return new Kmap( $map, true );
}

function mutableEmptyMap(): Kmap {
	return new Kmap( [], true );
}
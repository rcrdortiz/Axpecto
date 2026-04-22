<?php

namespace Axpecto\Collection;

use Exception;
use PHPUnit\Framework\TestCase;

class KmapTest extends TestCase {

	// ===============================================================
	// Core reads: toArray, isEmpty, isNotEmpty, count, firstOrNull
	// ===============================================================

	public function testToArray(): void {
		$map = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$this->assertSame( [ 'a' => 1, 'b' => 2 ], $map->toArray() );
	}

	public function testIsEmpty(): void {
		$this->assertTrue( emptyMap()->isEmpty() );
		$this->assertFalse( mapOf( [ 'x' => 1 ] )->isEmpty() );
	}

	public function testIsNotEmpty(): void {
		$this->assertTrue( mapOf( [ 'x' => 1 ] )->isNotEmpty() );
		$this->assertFalse( emptyMap()->isNotEmpty() );
	}

	public function testCount(): void {
		$this->assertSame( 0, emptyMap()->count() );
		$this->assertSame( 3, mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )->count() );
	}

	public function testFirstOrNull(): void {
		$this->assertNull( emptyMap()->firstOrNull() );
		$this->assertSame( 10, mapOf( [ 'a' => 10, 'b' => 20 ] )->firstOrNull() );
	}

	// ===============================================================
	// map — preserves keys
	// ===============================================================

	public function testMapPreservesKeys(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )
			->map( fn( $k, $v ) => $v * 10 )
			->toArray();

		$this->assertSame( [ 'a' => 10, 'b' => 20, 'c' => 30 ], $result );
	}

	public function testMapReceivesKeyAndValue(): void {
		$result = mapOf( [ 'x' => 5 ] )
			->map( fn( $k, $v ) => "$k=$v" )
			->toArray();

		$this->assertSame( [ 'x' => 'x=5' ], $result );
	}

	// ===============================================================
	// mapValues / mapKeys
	// ===============================================================

	public function testMapValuesIsAliasForMap(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2 ] )
			->mapValues( fn( $k, $v ) => $v + 100 )
			->toArray();

		$this->assertSame( [ 'a' => 101, 'b' => 102 ], $result );
	}

	public function testMapKeys(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2 ] )
			->mapKeys( fn( $k, $v ) => strtoupper( $k ) )
			->toArray();

		$this->assertSame( [ 'A' => 1, 'B' => 2 ], $result );
	}

	public function testMapKeysReceivesKeyAndValue(): void {
		$result = mapOf( [ 'x' => 10 ] )
			->mapKeys( fn( $k, $v ) => $k . $v )
			->toArray();

		$this->assertSame( [ 'x10' => 10 ], $result );
	}

	// ===============================================================
	// filter / filterNotNull / filterKeys / filterValues
	// ===============================================================

	public function testFilterReceivesKeyAndValue(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )
			->filter( fn( $k, $v ) => $v > 1 )
			->toArray();

		$this->assertSame( [ 'b' => 2, 'c' => 3 ], $result );
	}

	public function testFilterByKey(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )
			->filter( fn( $k, $v ) => $k !== 'b' )
			->toArray();

		$this->assertSame( [ 'a' => 1, 'c' => 3 ], $result );
	}

	public function testFilterNotNull(): void {
		$result = mapOf( [ 'a' => 1, 'b' => null, 'c' => 3, 'd' => null ] )
			->filterNotNull()
			->toArray();

		$this->assertSame( [ 'a' => 1, 'c' => 3 ], $result );
	}

	public function testFilterKeys(): void {
		$result = mapOf( [ 'apple' => 1, 'banana' => 2, 'avocado' => 3 ] )
			->filterKeys( fn( $k ) => str_starts_with( $k, 'a' ) )
			->toArray();

		$this->assertSame( [ 'apple' => 1, 'avocado' => 3 ], $result );
	}

	public function testFilterValues(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 20, 'c' => 3, 'd' => 40 ] )
			->filterValues( fn( $v ) => $v >= 10 )
			->toArray();

		$this->assertSame( [ 'b' => 20, 'd' => 40 ], $result );
	}

	// ===============================================================
	// any / all / none
	// ===============================================================

	public function testAny(): void {
		$this->assertTrue( mapOf( [ 'a' => 1, 'b' => 2 ] )->any( fn( $v ) => $v === 2 ) );
		$this->assertFalse( mapOf( [ 'a' => 1, 'b' => 2 ] )->any( fn( $v ) => $v === 99 ) );
	}

	public function testAnyOnEmpty(): void {
		$this->assertFalse( emptyMap()->any( fn( $v ) => true ) );
	}

	public function testAll(): void {
		$this->assertTrue( mapOf( [ 'a' => 2, 'b' => 4 ] )->all( fn( $v ) => $v % 2 === 0 ) );
		$this->assertFalse( mapOf( [ 'a' => 2, 'b' => 3 ] )->all( fn( $v ) => $v % 2 === 0 ) );
	}

	public function testAllOnEmpty(): void {
		$this->assertTrue( emptyMap()->all( fn( $v ) => false ) );
	}

	public function testNone(): void {
		$this->assertTrue( mapOf( [ 'a' => 1, 'b' => 2 ] )->none( fn( $v ) => $v > 10 ) );
		$this->assertFalse( mapOf( [ 'a' => 1, 'b' => 2 ] )->none( fn( $v ) => $v === 1 ) );
	}

	// ===============================================================
	// foreach / onEach
	// ===============================================================

	public function testForeach(): void {
		$collected = [];
		$map       = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$returned  = $map->foreach( function ( $k, $v ) use ( &$collected ) {
			$collected[ $k ] = $v;
		} );

		$this->assertSame( [ 'a' => 1, 'b' => 2 ], $collected );
		$this->assertSame( $map, $returned );
	}

	public function testOnEachIsAliasForForeach(): void {
		$collected = [];
		mapOf( [ 'x' => 10 ] )->onEach( function ( $k, $v ) use ( &$collected ) {
			$collected[ $k ] = $v;
		} );

		$this->assertSame( [ 'x' => 10 ], $collected );
	}

	// ===============================================================
	// reduce
	// ===============================================================

	public function testReduce(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )
			->reduce( fn( $carry, $v ) => $carry + $v, 0 );

		$this->assertSame( 6, $result );
	}

	public function testReduceDefaultInitial(): void {
		$result = mapOf( [ 'a' => 'hello', 'b' => ' world' ] )
			->reduce( fn( $carry, $v ) => ( $carry ?? '' ) . $v );

		$this->assertSame( 'hello world', $result );
	}

	// ===============================================================
	// sumOf
	// ===============================================================

	public function testSumOf(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )
			->sumOf( fn( $k, $v ) => $v * 10 );

		$this->assertSame( 60, $result );
	}

	public function testSumOfUsesKey(): void {
		$result = mapOf( [ 'ab' => 0, 'cdef' => 0 ] )
			->sumOf( fn( $k, $v ) => strlen( $k ) );

		$this->assertSame( 6, $result );
	}

	public function testSumOfEmpty(): void {
		$this->assertSame( 0, emptyMap()->sumOf( fn( $k, $v ) => $v ) );
	}

	// ===============================================================
	// minByOrNull / maxByOrNull
	// ===============================================================

	public function testMinByOrNull(): void {
		$result = mapOf( [ 'a' => 30, 'b' => 10, 'c' => 20 ] )
			->minByOrNull( fn( $k, $v ) => $v );

		$this->assertSame( 10, $result );
	}

	public function testMinByOrNullOnEmpty(): void {
		$this->assertNull( emptyMap()->minByOrNull( fn( $k, $v ) => $v ) );
	}

	public function testMaxByOrNull(): void {
		$result = mapOf( [ 'a' => 30, 'b' => 10, 'c' => 20 ] )
			->maxByOrNull( fn( $k, $v ) => $v );

		$this->assertSame( 30, $result );
	}

	public function testMaxByOrNullOnEmpty(): void {
		$this->assertNull( emptyMap()->maxByOrNull( fn( $k, $v ) => $v ) );
	}

	// ===============================================================
	// getOrDefault
	// ===============================================================

	public function testGetOrDefaultReturnsValueWhenKeyExists(): void {
		$map = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$this->assertSame( 1, $map->getOrDefault( 'a', 99 ) );
	}

	public function testGetOrDefaultReturnsDefaultWhenKeyMissing(): void {
		$map = mapOf( [ 'a' => 1 ] );
		$this->assertSame( 99, $map->getOrDefault( 'z', 99 ) );
	}

	// ===============================================================
	// getOrPut (mutable only)
	// ===============================================================

	public function testGetOrPutReturnsExistingValue(): void {
		$map = mutableMapOf( [ 'a' => 1 ] );
		$this->assertSame( 1, $map->getOrPut( 'a', fn() => 999 ) );
		$this->assertSame( 1, $map['a'] );
	}

	public function testGetOrPutComputesAndStoresNewValue(): void {
		$map = mutableMapOf( [ 'a' => 1 ] );

		$result = $map->getOrPut( 'b', fn() => 42 );

		$this->assertSame( 42, $result );
		$this->assertSame( 42, $map['b'] );
		$this->assertSame( 2, $map->count() );
	}

	public function testGetOrPutThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		mapOf( [ 'a' => 1 ] )->getOrPut( 'b', fn() => 2 );
	}

	// ===============================================================
	// containsKey / containsValue
	// ===============================================================

	public function testContainsKey(): void {
		$map = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$this->assertTrue( $map->containsKey( 'a' ) );
		$this->assertFalse( $map->containsKey( 'z' ) );
	}

	public function testContainsValue(): void {
		$map = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$this->assertTrue( $map->containsValue( 2 ) );
		$this->assertFalse( $map->containsValue( 99 ) );
	}

	public function testContainsValueUsesStrictComparison(): void {
		$this->assertFalse( mapOf( [ 'a' => 1 ] )->containsValue( '1' ) );
	}

	// ===============================================================
	// keys / values / entries / toList
	// ===============================================================

	public function testKeys(): void {
		$keys = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] )->keys();
		$this->assertInstanceOf( Klist::class, $keys );
		$this->assertSame( [ 'a', 'b', 'c' ], $keys->toArray() );
	}

	public function testValues(): void {
		$values = mapOf( [ 'a' => 10, 'b' => 20 ] )->values();
		$this->assertInstanceOf( Klist::class, $values );
		$this->assertSame( [ 10, 20 ], $values->toArray() );
	}

	public function testEntries(): void {
		$entries = mapOf( [ 'x' => 1, 'y' => 2 ] )->entries();
		$this->assertInstanceOf( Klist::class, $entries );
		$this->assertSame( [ [ 'x', 1 ], [ 'y', 2 ] ], $entries->toArray() );
	}

	public function testToList(): void {
		$list = mapOf( [ 'a' => 10, 'b' => 20 ] )->toList();
		$this->assertInstanceOf( Klist::class, $list );
		$this->assertSame( [ 10, 20 ], $list->toArray() );
	}

	// ===============================================================
	// join
	// ===============================================================

	public function testJoin(): void {
		$this->assertSame( 'a,b,c', mapOf( [ 'x' => 'a', 'y' => 'b', 'z' => 'c' ] )->join( ',' ) );
	}

	public function testJoinEmpty(): void {
		$this->assertSame( '', emptyMap()->join( ',' ) );
	}

	public function testJoinThrowsOnNonStringValues(): void {
		$this->expectException( Exception::class );
		mapOf( [ 'a' => 1, 'b' => 2 ] )->join( ',' );
	}

	// ===============================================================
	// merge / mergeArray
	// ===============================================================

	public function testMergeArray(): void {
		$result = mapOf( [ 'a' => 1, 'b' => 2 ] )
			->mergeArray( [ 'c' => 3 ] )
			->toArray();

		$this->assertArrayHasKey( 'a', $result );
		$this->assertArrayHasKey( 'b', $result );
		$this->assertArrayHasKey( 'c', $result );
	}

	public function testMerge(): void {
		$a = mapOf( [ 'a' => 1 ] );
		$b = mapOf( [ 'b' => 2 ] );

		$result = $a->merge( $b )->toArray();
		$this->assertArrayHasKey( 'a', $result );
		$this->assertArrayHasKey( 'b', $result );
	}

	public function testMergeReturnsNewInstanceForImmutable(): void {
		$original = mapOf( [ 'a' => 1 ] );
		$merged   = $original->mergeArray( [ 'b' => 2 ] );

		$this->assertSame( [ 'a' => 1 ], $original->toArray() );
		$this->assertArrayHasKey( 'b', $merged->toArray() );
	}

	// ===============================================================
	// flatten
	// ===============================================================

	public function testFlatten(): void {
		$result = mapOf( [ 'a' => [ 1, 2 ], 'b' => [ 3, 4 ] ] )
			->flatten()
			->toArray();

		$this->assertSame( [ 1, 2, 3, 4 ], $result );
	}

	public function testFlattenWithCollections(): void {
		$result = mapOf( [ 'a' => listOf( 1, 2 ), 'b' => listOf( 3 ) ] )
			->flatten()
			->toArray();

		$this->assertSame( [ 1, 2, 3 ], $result );
	}

	public function testFlattenNonArrayValuesPassThrough(): void {
		$result = mapOf( [ 'a' => 'hello', 'b' => [ 1, 2 ] ] )
			->flatten()
			->toArray();

		$this->assertSame( [ 'hello', 1, 2 ], $result );
	}

	// ===============================================================
	// maybe
	// ===============================================================

	public function testMaybeCallsPredicateWhenNotEmpty(): void {
		$called = false;
		mapOf( [ 'a' => 1 ] )->maybe( function () use ( &$called ) {
			$called = true;
		} );
		$this->assertTrue( $called );
	}

	public function testMaybeDoesNotCallPredicateWhenEmpty(): void {
		$called = false;
		emptyMap()->maybe( function () use ( &$called ) {
			$called = true;
		} );
		$this->assertFalse( $called );
	}

	// ===============================================================
	// toMutable / toImmutable
	// ===============================================================

	public function testToMutableAndToImmutable(): void {
		$immutable = mapOf( [ 'a' => 1 ] );
		$mutable   = $immutable->toMutable();

		$mutable->add( 'b', 2 );
		$this->assertSame( 2, $mutable['b'] );

		// Original unchanged.
		$this->assertFalse( $immutable->containsKey( 'b' ) );

		// Convert back to immutable.
		$back = $mutable->toImmutable();
		$this->expectException( Exception::class );
		$back->add( 'c', 3 );
	}

	public function testToMutableReturnsSelfWhenAlreadyMutable(): void {
		$mutable = mutableMapOf( [ 'a' => 1 ] );
		$this->assertSame( $mutable, $mutable->toMutable() );
	}

	public function testToImmutableReturnsSelfWhenAlreadyImmutable(): void {
		$immutable = mapOf( [ 'a' => 1 ] );
		$this->assertSame( $immutable, $immutable->toImmutable() );
	}

	// ===============================================================
	// Mutation: add / remove / offsetSet / offsetUnset
	// ===============================================================

	public function testAddOnMutable(): void {
		$map = mutableMapOf( [ 'a' => 1 ] );
		$map->add( 'b', 2 );
		$this->assertSame( [ 'a' => 1, 'b' => 2 ], $map->toArray() );
	}

	public function testAddThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		mapOf( [ 'a' => 1 ] )->add( 'b', 2 );
	}

	public function testRemoveOnMutable(): void {
		$map = mutableMapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] );
		$map->remove( 'b' );
		$this->assertSame( [ 'a' => 1, 'c' => 3 ], $map->toArray() );
	}

	public function testRemoveThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		mapOf( [ 'a' => 1 ] )->remove( 'a' );
	}

	public function testOffsetSetOnMutable(): void {
		$map        = mutableMapOf( [ 'a' => 1 ] );
		$map['a']   = 99;
		$map['new'] = 42;
		$this->assertSame( 99, $map['a'] );
		$this->assertSame( 42, $map['new'] );
	}

	public function testOffsetSetThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		$map      = mapOf( [ 'a' => 1 ] );
		$map['a'] = 2;
	}

	public function testOffsetUnsetOnMutable(): void {
		$map = mutableMapOf( [ 'a' => 1, 'b' => 2 ] );
		unset( $map['a'] );
		$this->assertSame( [ 'b' => 2 ], $map->toArray() );
	}

	public function testOffsetUnsetThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		$map = mapOf( [ 'a' => 1 ] );
		unset( $map['a'] );
	}

	// ===============================================================
	// Mutable operations mutate in place
	// ===============================================================

	public function testMutableFilterMutatesInPlace(): void {
		$map  = mutableMapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] );
		$same = $map->filter( fn( $k, $v ) => $v > 1 );
		$this->assertSame( $map, $same );
		$this->assertSame( [ 'b' => 2, 'c' => 3 ], $map->toArray() );
	}

	public function testMutableMapMutatesInPlace(): void {
		$map  = mutableMapOf( [ 'a' => 1, 'b' => 2 ] );
		$same = $map->map( fn( $k, $v ) => $v * 10 );
		$this->assertSame( $map, $same );
		$this->assertSame( [ 'a' => 10, 'b' => 20 ], $map->toArray() );
	}

	public function testMutableMergeArrayMutatesInPlace(): void {
		$map  = mutableMapOf( [ 'a' => 1 ] );
		$same = $map->mergeArray( [ 'b' => 2 ] );
		$this->assertSame( $map, $same );
		$this->assertTrue( $map->containsKey( 'b' ) );
	}

	// ===============================================================
	// Iterator: rewind, current, key, next, valid — lazy key building
	// ===============================================================

	public function testIterator(): void {
		$map      = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] );
		$iterated = [];
		foreach ( $map as $key => $value ) {
			$iterated[ $key ] = $value;
		}

		$this->assertSame( [ 'a' => 1, 'b' => 2, 'c' => 3 ], $iterated );
	}

	public function testIteratorLazyKeyBuilding(): void {
		// Keys are built lazily on first iteration — verify by calling rewind
		// which nullifies the cached keys.
		$map = mapOf( [ 'x' => 10, 'y' => 20 ] );

		$map->rewind();
		$this->assertSame( 'x', $map->key() );
		$this->assertSame( 10, $map->current() );
		$this->assertTrue( $map->valid() );

		$map->next();
		$this->assertSame( 'y', $map->key() );
		$this->assertSame( 20, $map->current() );
		$this->assertTrue( $map->valid() );

		$map->next();
		$this->assertFalse( $map->valid() );
		$this->assertNull( $map->key() );
		$this->assertNull( $map->current() );
	}

	public function testNextAndGet(): void {
		$map = mapOf( [ 'a' => 10, 'b' => 20 ] );
		$this->assertSame( 10, $map->nextAndGet() );
		$this->assertSame( 20, $map->nextAndGet() );
		$this->assertNull( $map->nextAndGet() );
	}

	public function testRewindResetsIterator(): void {
		$map = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$map->nextAndGet();
		$map->nextAndGet();

		$map->rewind();
		$this->assertSame( 1, $map->current() );
		$this->assertSame( 'a', $map->key() );
	}

	public function testIteratorKeysInvalidatedOnMutation(): void {
		$map = mutableMapOf( [ 'a' => 1, 'b' => 2 ] );

		// Iterate partially.
		$map->rewind();
		$map->nextAndGet();

		// Mutate — keys should be invalidated.
		$map->add( 'c', 3 );

		// Rewind and iterate again — new key should be present.
		$map->rewind();
		$keys = [];
		foreach ( $map as $key => $value ) {
			$keys[] = $key;
		}
		$this->assertContains( 'c', $keys );
	}

	// ===============================================================
	// ArrayAccess: offsetExists, offsetGet
	// ===============================================================

	public function testOffsetExists(): void {
		$map = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$this->assertTrue( isset( $map['a'] ) );
		$this->assertFalse( isset( $map['z'] ) );
	}

	public function testOffsetGet(): void {
		$map = mapOf( [ 'key' => 'value' ] );
		$this->assertSame( 'value', $map['key'] );
	}

	// ===============================================================
	// JsonSerializable
	// ===============================================================

	public function testJsonSerialize(): void {
		$map  = mapOf( [ 'a' => 1, 'b' => 2 ] );
		$json = json_encode( $map );
		$this->assertSame( '{"a":1,"b":2}', $json );
	}

	// ===============================================================
	// Edge cases
	// ===============================================================

	public function testEmptyMapEdgeCases(): void {
		$empty = emptyMap();

		$this->assertSame( [], $empty->toArray() );
		$this->assertSame( 0, $empty->count() );
		$this->assertTrue( $empty->isEmpty() );
		$this->assertFalse( $empty->isNotEmpty() );
		$this->assertNull( $empty->firstOrNull() );
		$this->assertFalse( $empty->any( fn( $v ) => true ) );
		$this->assertTrue( $empty->all( fn( $v ) => false ) );
		$this->assertTrue( $empty->none( fn( $v ) => true ) );
		$this->assertSame( [], $empty->keys()->toArray() );
		$this->assertSame( [], $empty->values()->toArray() );
		$this->assertSame( [], $empty->entries()->toArray() );
		$this->assertFalse( $empty->containsKey( 'a' ) );
		$this->assertFalse( $empty->containsValue( 1 ) );
	}

	public function testSingleEntryMap(): void {
		$map = mapOf( [ 'only' => 42 ] );

		$this->assertSame( 1, $map->count() );
		$this->assertSame( 42, $map->firstOrNull() );
		$this->assertSame( 42, $map['only'] );
		$this->assertTrue( $map->containsKey( 'only' ) );
		$this->assertTrue( $map->containsValue( 42 ) );
		$this->assertSame( [ 'only' ], $map->keys()->toArray() );
		$this->assertSame( [ 42 ], $map->values()->toArray() );
	}

	public function testNumericKeys(): void {
		$map = mapOf( [ 0 => 'zero', 1 => 'one', 2 => 'two' ] );
		$this->assertSame( 'zero', $map[0] );
		$this->assertTrue( $map->containsKey( 1 ) );
		$this->assertSame( [ 0, 1, 2 ], $map->keys()->toArray() );
	}

	public function testNullValuesAreHandled(): void {
		$map = mapOf( [ 'a' => null, 'b' => 1 ] );
		$this->assertNull( $map['a'] );
		$this->assertTrue( $map->containsValue( 1 ) );
		// containsValue uses strict comparison — null check.
		$this->assertTrue( $map->containsValue( null ) );
	}

	// ===============================================================
	// Helper functions
	// ===============================================================

	public function testMapOfHelper(): void {
		$map = mapOf( [ 'k' => 'v' ] );
		$this->assertInstanceOf( Kmap::class, $map );
		$this->assertSame( [ 'k' => 'v' ], $map->toArray() );
	}

	public function testEmptyMapHelper(): void {
		$map = emptyMap();
		$this->assertInstanceOf( Kmap::class, $map );
		$this->assertTrue( $map->isEmpty() );
	}

	public function testMutableMapOfHelper(): void {
		$map = mutableMapOf( [ 'a' => 1 ] );
		$map->add( 'b', 2 );
		$this->assertSame( [ 'a' => 1, 'b' => 2 ], $map->toArray() );
	}

	public function testMutableEmptyMapHelper(): void {
		$map = mutableEmptyMap();
		$this->assertTrue( $map->isEmpty() );
		$map->add( 'key', 'val' );
		$this->assertSame( [ 'key' => 'val' ], $map->toArray() );
	}

	// ===============================================================
	// Immutable operations return new instances
	// ===============================================================

	public function testImmutableFilterReturnsNewInstance(): void {
		$original = mapOf( [ 'a' => 1, 'b' => 2, 'c' => 3 ] );
		$filtered = $original->filter( fn( $k, $v ) => $v > 1 );

		$this->assertNotSame( $original, $filtered );
		$this->assertSame( [ 'a' => 1, 'b' => 2, 'c' => 3 ], $original->toArray() );
		$this->assertSame( [ 'b' => 2, 'c' => 3 ], $filtered->toArray() );
	}

	public function testImmutableMapReturnsNewInstance(): void {
		$original = mapOf( [ 'a' => 1 ] );
		$mapped   = $original->map( fn( $k, $v ) => $v * 10 );

		$this->assertNotSame( $original, $mapped );
		$this->assertSame( [ 'a' => 1 ], $original->toArray() );
		$this->assertSame( [ 'a' => 10 ], $mapped->toArray() );
	}
}

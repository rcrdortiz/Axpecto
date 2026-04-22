<?php

namespace Axpecto\Collection;

use Exception;
use PHPUnit\Framework\TestCase;

class KlistTest extends TestCase {

	// ===============================================================
	// Lazy evaluation
	// ===============================================================

	public function testIntermediateOperationsDoNotMaterializeUntilTerminal(): void {
		$callCount = 0;
		$list      = listOf( 1, 2, 3 );

		// Chain intermediates — nothing should execute yet.
		$lazy = $list
			->filter( function ( $v ) use ( &$callCount ) {
				$callCount++;
				return $v > 1;
			} )
			->map( fn( $v ) => $v * 10 );

		$this->assertSame( 0, $callCount, 'Filter must NOT execute before a terminal is called' );

		// Terminal forces evaluation.
		$lazy->toArray();
		$this->assertGreaterThan( 0, $callCount );
	}

	public function testShortCircuitFirstOrNullDoesNotProcessAllElements(): void {
		$visited = [];
		$list    = listOf( 1, 2, 3, 4, 5 );

		$result = $list
			->filter( function ( $v ) use ( &$visited ) {
				$visited[] = $v;
				return $v > 2;
			} )
			->firstOrNull();

		$this->assertSame( 3, $result );
		// Should stop after finding 3 — elements 4 and 5 must not be visited.
		$this->assertNotContains( 4, $visited );
		$this->assertNotContains( 5, $visited );
	}

	public function testShortCircuitAnyDoesNotProcessAllElements(): void {
		$visited = [];
		$list    = listOf( 1, 2, 3, 4, 5 );

		$result = $list
			->map( function ( $v ) use ( &$visited ) {
				$visited[] = $v;
				return $v;
			} )
			->any( fn( $v ) => $v === 2 );

		$this->assertTrue( $result );
		$this->assertNotContains( 3, $visited );
	}

	// ===============================================================
	// flatMap
	// ===============================================================

	public function testFlatMap(): void {
		$result = listOf( 1, 2, 3 )
			->flatMap( fn( $v ) => [ $v, $v * 10 ] )
			->toArray();

		$this->assertSame( [ 1, 10, 2, 20, 3, 30 ], $result );
	}

	public function testFlatMapWithCollectionReturn(): void {
		$result = listOf( 1, 2 )
			->flatMap( fn( $v ) => listOf( $v, $v + 10 ) )
			->toArray();

		$this->assertSame( [ 1, 11, 2, 12 ], $result );
	}

	// ===============================================================
	// mapNotNull
	// ===============================================================

	public function testMapNotNull(): void {
		$result = listOf( 1, 2, 3, 4 )
			->mapNotNull( fn( $v ) => $v % 2 === 0 ? $v * 10 : null )
			->toArray();

		$this->assertSame( [ 20, 40 ], $result );
	}

	// ===============================================================
	// first / last / single
	// ===============================================================

	public function testFirst(): void {
		$this->assertSame( 'a', listOf( 'a', 'b', 'c' )->first() );
	}

	public function testFirstThrowsOnEmpty(): void {
		$this->expectException( Exception::class );
		emptyList()->first();
	}

	public function testFirstOrNullReturnsNullOnEmpty(): void {
		$this->assertNull( emptyList()->firstOrNull() );
	}

	public function testFirstOrNullReturnFirstElement(): void {
		$this->assertSame( 42, listOf( 42, 99 )->firstOrNull() );
	}

	public function testLast(): void {
		$this->assertSame( 'c', listOf( 'a', 'b', 'c' )->last() );
	}

	public function testLastThrowsOnEmpty(): void {
		$this->expectException( Exception::class );
		emptyList()->last();
	}

	public function testLastOrNull(): void {
		$this->assertSame( 3, listOf( 1, 2, 3 )->lastOrNull() );
	}

	public function testLastOrNullReturnsNullOnEmpty(): void {
		$this->assertNull( emptyList()->lastOrNull() );
	}

	public function testSingle(): void {
		$this->assertSame( 7, listOf( 7 )->single() );
	}

	public function testSingleThrowsOnEmpty(): void {
		$this->expectException( Exception::class );
		emptyList()->single();
	}

	public function testSingleThrowsOnMultipleElements(): void {
		$this->expectException( Exception::class );
		listOf( 1, 2 )->single();
	}

	public function testSingleOrNull(): void {
		$this->assertSame( 7, listOf( 7 )->singleOrNull() );
	}

	public function testSingleOrNullReturnsNullOnEmpty(): void {
		$this->assertNull( emptyList()->singleOrNull() );
	}

	public function testSingleOrNullReturnsNullOnMultiple(): void {
		$this->assertNull( listOf( 1, 2 )->singleOrNull() );
	}

	// ===============================================================
	// take / drop
	// ===============================================================

	public function testTake(): void {
		$this->assertSame( [ 1, 2 ], listOf( 1, 2, 3, 4, 5 )->take( 2 )->toArray() );
	}

	public function testTakeMoreThanSize(): void {
		$this->assertSame( [ 1, 2 ], listOf( 1, 2 )->take( 10 )->toArray() );
	}

	public function testDrop(): void {
		$this->assertSame( [ 3, 4, 5 ], listOf( 1, 2, 3, 4, 5 )->drop( 2 )->toArray() );
	}

	public function testDropMoreThanSize(): void {
		$this->assertSame( [], listOf( 1, 2 )->drop( 10 )->toArray() );
	}

	// ===============================================================
	// distinct / distinctBy
	// ===============================================================

	public function testDistinct(): void {
		$this->assertSame( [ 1, 2, 3 ], listOf( 1, 2, 2, 3, 1 )->distinct()->toArray() );
	}

	public function testDistinctBy(): void {
		$result = listOf( 'apple', 'avocado', 'banana', 'blueberry' )
			->distinctBy( fn( $s ) => $s[0] )
			->toArray();

		$this->assertSame( [ 'apple', 'banana' ], $result );
	}

	// ===============================================================
	// sortedBy / sortedByDescending / reversed
	// ===============================================================

	public function testSortedBy(): void {
		$result = listOf( 3, 1, 2 )->sortedBy( fn( $v ) => $v )->toArray();
		$this->assertSame( [ 1, 2, 3 ], $result );
	}

	public function testSortedByDescending(): void {
		$result = listOf( 3, 1, 2 )->sortedByDescending( fn( $v ) => $v )->toArray();
		$this->assertSame( [ 3, 2, 1 ], $result );
	}

	public function testReversed(): void {
		$this->assertSame( [ 3, 2, 1 ], listOf( 1, 2, 3 )->reversed()->toArray() );
	}

	// ===============================================================
	// onEach (lazy side-effect)
	// ===============================================================

	public function testOnEachIsLazy(): void {
		$seen = [];
		$lazy = listOf( 1, 2, 3 )->onEach( function ( $v ) use ( &$seen ) {
			$seen[] = $v;
		} );

		$this->assertSame( [], $seen, 'onEach must not execute before terminal' );

		$lazy->toArray();
		$this->assertSame( [ 1, 2, 3 ], $seen );
	}

	// ===============================================================
	// chunked
	// ===============================================================

	public function testChunked(): void {
		$result = listOf( 1, 2, 3, 4, 5 )->chunked( 2 )->toArray();
		$this->assertSame( [ [ 1, 2 ], [ 3, 4 ], [ 5 ] ], $result );
	}

	// ===============================================================
	// none / contains / indexOf / indexOfFirst
	// ===============================================================

	public function testNone(): void {
		$this->assertTrue( listOf( 1, 2, 3 )->none( fn( $v ) => $v > 10 ) );
		$this->assertFalse( listOf( 1, 2, 3 )->none( fn( $v ) => $v === 2 ) );
	}

	public function testContains(): void {
		$list = listOf( 'a', 'b', 'c' );
		$this->assertTrue( $list->contains( 'b' ) );
		$this->assertFalse( $list->contains( 'z' ) );
	}

	public function testContainsUsesStrictComparison(): void {
		$this->assertFalse( listOf( 1, 2, 3 )->contains( '1' ) );
	}

	public function testIndexOf(): void {
		$this->assertSame( 1, listOf( 'a', 'b', 'c' )->indexOf( 'b' ) );
	}

	public function testIndexOfNotFound(): void {
		$this->assertSame( -1, listOf( 'a', 'b' )->indexOf( 'z' ) );
	}

	public function testIndexOfFirst(): void {
		$this->assertSame( 2, listOf( 1, 2, 3, 4 )->indexOfFirst( fn( $v ) => $v > 2 ) );
	}

	public function testIndexOfFirstNotFound(): void {
		$this->assertSame( -1, listOf( 1, 2 )->indexOfFirst( fn( $v ) => $v > 100 ) );
	}

	// ===============================================================
	// fold / reduce / sumOf
	// ===============================================================

	public function testFold(): void {
		$result = listOf( 1, 2, 3 )->fold( 10, fn( $acc, $v ) => $acc + $v );
		$this->assertSame( 16, $result );
	}

	public function testReduce(): void {
		$result = listOf( 1, 2, 3 )->reduce( fn( $carry, $v ) => ( $carry ?? 0 ) + $v );
		$this->assertSame( 6, $result );
	}

	public function testReduceWithInitial(): void {
		$result = listOf( 1, 2, 3 )->reduce( fn( $carry, $v ) => $carry + $v, 100 );
		$this->assertSame( 106, $result );
	}

	public function testSumOf(): void {
		$this->assertSame( 14, listOf( 1, 2, 3, 4 )->sumOf( fn( $v ) => $v + $v / $v ) );
	}

	public function testSumOfFloat(): void {
		$result = listOf( 1.5, 2.5 )->sumOf( fn( $v ) => $v );
		$this->assertSame( 4.0, $result );
	}

	// ===============================================================
	// minByOrNull / maxByOrNull
	// ===============================================================

	public function testMinByOrNull(): void {
		$result = listOf( 'banana', 'a', 'cherry' )->minByOrNull( fn( $v ) => strlen( $v ) );
		$this->assertSame( 'a', $result );
	}

	public function testMinByOrNullOnEmpty(): void {
		$this->assertNull( emptyList()->minByOrNull( fn( $v ) => $v ) );
	}

	public function testMaxByOrNull(): void {
		$result = listOf( 'banana', 'a', 'kiwifruit' )->maxByOrNull( fn( $v ) => strlen( $v ) );
		$this->assertSame( 'kiwifruit', $result );
	}

	public function testMaxByOrNullOnEmpty(): void {
		$this->assertNull( emptyList()->maxByOrNull( fn( $v ) => $v ) );
	}

	// ===============================================================
	// partition
	// ===============================================================

	public function testPartition(): void {
		[ $evens, $odds ] = listOf( 1, 2, 3, 4, 5 )->partition( fn( $v ) => $v % 2 === 0 );

		$this->assertSame( [ 2, 4 ], $evens->toArray() );
		$this->assertSame( [ 1, 3, 5 ], $odds->toArray() );
	}

	// ===============================================================
	// groupBy
	// ===============================================================

	public function testGroupBy(): void {
		$groups = listOf( 'one', 'two', 'three', 'four' )
			->groupBy( fn( $s ) => strlen( $s ) );

		$this->assertInstanceOf( Kmap::class, $groups );
		$this->assertSame( [ 'one', 'two' ], $groups[3]->toArray() );
		$this->assertSame( [ 'three' ], $groups[5]->toArray() );
		$this->assertSame( [ 'four' ], $groups[4]->toArray() );
	}

	// ===============================================================
	// associate / associateBy / mapOf
	// ===============================================================

	public function testAssociate(): void {
		$map = listOf( 'a', 'bb', 'ccc' )
			->associate( fn( $v ) => [ $v => strlen( $v ) ] );

		$this->assertInstanceOf( Kmap::class, $map );
		$this->assertSame( [ 'a' => 1, 'bb' => 2, 'ccc' => 3 ], $map->toArray() );
	}

	public function testAssociateBy(): void {
		$map = listOf( 'a', 'bb', 'ccc' )
			->associateBy( fn( $v ) => strlen( $v ) );

		$this->assertSame( [ 1 => 'a', 2 => 'bb', 3 => 'ccc' ], $map->toArray() );
	}

	public function testMapOfIsAliasForAssociate(): void {
		$map = listOf( 'x', 'yy' )->mapOf( fn( $v ) => [ $v => strlen( $v ) ] );

		$this->assertInstanceOf( Kmap::class, $map );
		$this->assertSame( [ 'x' => 1, 'yy' => 2 ], $map->toArray() );
	}

	// ===============================================================
	// zip
	// ===============================================================

	public function testZip(): void {
		$result = listOf( 'a', 'b', 'c' )->zip( [ 1, 2, 3 ] )->toArray();
		$this->assertSame( [ [ 'a', 1 ], [ 'b', 2 ], [ 'c', 3 ] ], $result );
	}

	public function testZipWithUnequalLengths(): void {
		$result = listOf( 'a', 'b' )->zip( [ 1, 2, 3, 4 ] )->toArray();
		$this->assertSame( [ [ 'a', 1 ], [ 'b', 2 ] ], $result );
	}

	public function testZipWithKlist(): void {
		$result = listOf( 1, 2 )->zip( listOf( 'x', 'y' ) )->toArray();
		$this->assertSame( [ [ 1, 'x' ], [ 2, 'y' ] ], $result );
	}

	// ===============================================================
	// filter / filterNotNull / map / flatten (existing methods)
	// ===============================================================

	public function testFilter(): void {
		$result = listOf( 1, 2, 3, 4 )->filter( fn( $v ) => $v > 2 )->toArray();
		$this->assertSame( [ 3, 4 ], $result );
	}

	public function testFilterNotNull(): void {
		$result = listOf( 1, null, 2, null, 3 )->filterNotNull()->toArray();
		$this->assertSame( [ 1, 2, 3 ], $result );
	}

	public function testMap(): void {
		$result = listOf( 1, 2, 3 )->map( fn( $v ) => $v * 2 )->toArray();
		$this->assertSame( [ 2, 4, 6 ], $result );
	}

	public function testFlatten(): void {
		$result = listOf( [ 1, 2 ], [ 3, 4 ], [ 5 ] )->flatten()->toArray();
		$this->assertSame( [ 1, 2, 3, 4, 5 ], $result );
	}

	public function testFlattenWithCollections(): void {
		$result = listOf( listOf( 1, 2 ), listOf( 3 ) )->flatten()->toArray();
		$this->assertSame( [ 1, 2, 3 ], $result );
	}

	public function testFlattenNonArrayElementsPassThrough(): void {
		$result = listOf( 'a', [ 'b', 'c' ], 'd' )->flatten()->toArray();
		$this->assertSame( [ 'a', 'b', 'c', 'd' ], $result );
	}

	// ===============================================================
	// join
	// ===============================================================

	public function testJoin(): void {
		$this->assertSame( 'a,b,c', listOf( 'a', 'b', 'c' )->join( ',' ) );
	}

	public function testJoinEmpty(): void {
		$this->assertSame( '', emptyList()->join( ',' ) );
	}

	// ===============================================================
	// any / all
	// ===============================================================

	public function testAny(): void {
		$this->assertTrue( listOf( 1, 2, 3 )->any( fn( $v ) => $v === 2 ) );
		$this->assertFalse( listOf( 1, 2, 3 )->any( fn( $v ) => $v === 99 ) );
	}

	public function testAll(): void {
		$this->assertTrue( listOf( 2, 4, 6 )->all( fn( $v ) => $v % 2 === 0 ) );
		$this->assertFalse( listOf( 2, 3, 6 )->all( fn( $v ) => $v % 2 === 0 ) );
	}

	// ===============================================================
	// foreach (terminal)
	// ===============================================================

	public function testForeach(): void {
		$collected = [];
		$returned  = listOf( 10, 20, 30 )->foreach( function ( $v ) use ( &$collected ) {
			$collected[] = $v;
		} );

		$this->assertSame( [ 10, 20, 30 ], $collected );
		$this->assertInstanceOf( Klist::class, $returned );
	}

	// ===============================================================
	// merge / mergeArray
	// ===============================================================

	public function testMerge(): void {
		$a = listOf( 1, 2 );
		$b = listOf( 3, 4 );

		$this->assertSame( [ 1, 2, 3, 4 ], $a->merge( $b )->toArray() );
	}

	public function testMergeArray(): void {
		$this->assertSame( [ 1, 2, 3, 4 ], listOf( 1, 2 )->mergeArray( [ 3, 4 ] )->toArray() );
	}

	public function testMergeReturnsNewInstanceForImmutable(): void {
		$original = listOf( 1, 2 );
		$merged   = $original->mergeArray( [ 3 ] );

		$this->assertSame( [ 1, 2 ], $original->toArray() );
		$this->assertSame( [ 1, 2, 3 ], $merged->toArray() );
	}

	// ===============================================================
	// isEmpty / isNotEmpty / count / toArray
	// ===============================================================

	public function testIsEmpty(): void {
		$this->assertTrue( emptyList()->isEmpty() );
		$this->assertFalse( listOf( 1 )->isEmpty() );
	}

	public function testIsNotEmpty(): void {
		$this->assertTrue( listOf( 1 )->isNotEmpty() );
		$this->assertFalse( emptyList()->isNotEmpty() );
	}

	public function testCount(): void {
		$this->assertSame( 0, emptyList()->count() );
		$this->assertSame( 3, listOf( 'a', 'b', 'c' )->count() );
	}

	public function testToArray(): void {
		$this->assertSame( [ 1, 2, 3 ], listOf( 1, 2, 3 )->toArray() );
	}

	public function testToArrayReindexes(): void {
		// Source built from assoc array reindexes to 0-based.
		$list = listFrom( [ 5 => 'a', 10 => 'b' ] );
		$this->assertSame( [ 'a', 'b' ], $list->toArray() );
	}

	// ===============================================================
	// maybe
	// ===============================================================

	public function testMaybeCallsPredicateWhenNotEmpty(): void {
		$called = false;
		listOf( 1 )->maybe( function () use ( &$called ) {
			$called = true;
		} );
		$this->assertTrue( $called );
	}

	public function testMaybeDoesNotCallPredicateWhenEmpty(): void {
		$called = false;
		emptyList()->maybe( function () use ( &$called ) {
			$called = true;
		} );
		$this->assertFalse( $called );
	}

	// ===============================================================
	// toMutable / toImmutable
	// ===============================================================

	public function testToMutableAndToImmutable(): void {
		$immutable = listOf( 1, 2 );
		$mutable   = $immutable->toMutable();

		// Mutable allows add.
		$mutable->add( 3 );
		$this->assertSame( [ 1, 2, 3 ], $mutable->toArray() );

		// Original unchanged.
		$this->assertSame( [ 1, 2 ], $immutable->toArray() );

		// Convert back to immutable.
		$back = $mutable->toImmutable();
		$this->expectException( Exception::class );
		$back->add( 4 );
	}

	public function testToMutableReturnsSelfWhenAlreadyMutable(): void {
		$mutable = mutableListOf( 1 );
		$this->assertSame( $mutable, $mutable->toMutable() );
	}

	public function testToImmutableReturnsSelfWhenAlreadyImmutable(): void {
		$immutable = listOf( 1 );
		$this->assertSame( $immutable, $immutable->toImmutable() );
	}

	// ===============================================================
	// Iterator: current / next / nextAndGet / key / valid / rewind
	// ===============================================================

	public function testIterator(): void {
		$list     = listOf( 'a', 'b', 'c' );
		$iterated = [];
		foreach ( $list as $key => $value ) {
			$iterated[ $key ] = $value;
		}

		$this->assertSame( [ 0 => 'a', 1 => 'b', 2 => 'c' ], $iterated );
	}

	public function testNextAndGet(): void {
		$list = listOf( 10, 20, 30 );
		$this->assertSame( 10, $list->nextAndGet() );
		$this->assertSame( 20, $list->nextAndGet() );
		$this->assertSame( 30, $list->nextAndGet() );
		$this->assertNull( $list->nextAndGet() );
	}

	public function testRewind(): void {
		$list = listOf( 1, 2 );
		$list->nextAndGet();
		$list->nextAndGet();
		$list->rewind();
		$this->assertSame( 1, $list->current() );
	}

	// ===============================================================
	// ArrayAccess
	// ===============================================================

	public function testOffsetExists(): void {
		$list = listOf( 'a', 'b' );
		$this->assertTrue( isset( $list[0] ) );
		$this->assertTrue( isset( $list[1] ) );
		$this->assertFalse( isset( $list[2] ) );
	}

	public function testOffsetGet(): void {
		$list = listOf( 'x', 'y' );
		$this->assertSame( 'x', $list[0] );
		$this->assertSame( 'y', $list[1] );
	}

	// ===============================================================
	// Mutable mode: add / offsetSet / offsetUnset
	// ===============================================================

	public function testAddThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		listOf( 1 )->add( 2 );
	}

	public function testMutableAdd(): void {
		$list = mutableListOf( 1, 2 );
		$list->add( 3 );
		$this->assertSame( [ 1, 2, 3 ], $list->toArray() );
	}

	public function testMutableOffsetSetAppend(): void {
		$list   = mutableListOf( 'a' );
		$list[] = 'b';
		$this->assertSame( [ 'a', 'b' ], $list->toArray() );
	}

	public function testMutableOffsetSetByIndex(): void {
		$list    = mutableListOf( 'a', 'b', 'c' );
		$list[1] = 'B';
		$this->assertSame( [ 'a', 'B', 'c' ], $list->toArray() );
	}

	public function testOffsetSetThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		$list    = listOf( 1 );
		$list[0] = 99;
	}

	public function testMutableOffsetUnset(): void {
		$list = mutableListOf( 'a', 'b', 'c' );
		unset( $list[1] );
		// After unset, array is re-indexed.
		$this->assertSame( [ 'a', 'c' ], $list->toArray() );
	}

	public function testOffsetUnsetThrowsOnImmutable(): void {
		$this->expectException( Exception::class );
		$list = listOf( 1, 2 );
		unset( $list[0] );
	}

	// ===============================================================
	// Mutable eagerly materializes
	// ===============================================================

	public function testMutableOperationsAreEager(): void {
		$count = 0;
		$list  = mutableListOf( 1, 2, 3 );
		$list->filter( function ( $v ) use ( &$count ) {
			$count++;
			return $v > 1;
		} );

		// Mutable applies immediately — count should be >0.
		$this->assertGreaterThan( 0, $count );
	}

	public function testMutableMergeArrayMutatesInPlace(): void {
		$list = mutableListOf( 1, 2 );
		$same = $list->mergeArray( [ 3, 4 ] );
		$this->assertSame( $list, $same );
		$this->assertSame( [ 1, 2, 3, 4 ], $list->toArray() );
	}

	// ===============================================================
	// Edge cases
	// ===============================================================

	public function testEmptyListEdgeCases(): void {
		$empty = emptyList();

		$this->assertSame( [], $empty->toArray() );
		$this->assertSame( 0, $empty->count() );
		$this->assertTrue( $empty->isEmpty() );
		$this->assertFalse( $empty->isNotEmpty() );
		$this->assertNull( $empty->firstOrNull() );
		$this->assertNull( $empty->lastOrNull() );
		$this->assertNull( $empty->singleOrNull() );
		$this->assertFalse( $empty->any( fn( $v ) => true ) );
		$this->assertTrue( $empty->all( fn( $v ) => false ) );
		$this->assertTrue( $empty->none( fn( $v ) => true ) );
		$this->assertSame( '', $empty->join( ',' ) );
		$this->assertSame( [], $empty->filter( fn( $v ) => true )->toArray() );
		$this->assertSame( [], $empty->map( fn( $v ) => $v )->toArray() );
		$this->assertSame( -1, $empty->indexOf( 'x' ) );
		$this->assertSame( -1, $empty->indexOfFirst( fn( $v ) => true ) );
		$this->assertFalse( $empty->contains( 1 ) );
	}

	public function testSingleElementList(): void {
		$list = listOf( 42 );

		$this->assertSame( 1, $list->count() );
		$this->assertSame( 42, $list->first() );
		$this->assertSame( 42, $list->last() );
		$this->assertSame( 42, $list->single() );
		$this->assertSame( 42, $list->firstOrNull() );
		$this->assertSame( 42, $list->lastOrNull() );
		$this->assertSame( 42, $list->singleOrNull() );
		$this->assertSame( 0, $list->indexOf( 42 ) );
	}

	public function testNullElementsArePreserved(): void {
		$list = listOf( null, 1, null, 2 );
		$this->assertSame( [ null, 1, null, 2 ], $list->toArray() );
		$this->assertSame( 4, $list->count() );
		$this->assertNull( $list->firstOrNull() );
	}

	// ===============================================================
	// Pipeline chaining combinations
	// ===============================================================

	public function testComplexPipelineChain(): void {
		$result = listOf( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 )
			->filter( fn( $v ) => $v % 2 === 0 )
			->map( fn( $v ) => $v * 3 )
			->take( 3 )
			->reversed()
			->toArray();

		$this->assertSame( [ 18, 12, 6 ], $result );
	}

	public function testFilterMapFirstOrNullShortCircuit(): void {
		$result = listOf( 1, 2, 3, 4 )
			->filter( fn( $v ) => $v > 2 )
			->map( fn( $v ) => $v * 100 )
			->firstOrNull();

		$this->assertSame( 300, $result );
	}

	// ===============================================================
	// JsonSerializable
	// ===============================================================

	public function testJsonSerialize(): void {
		$json = json_encode( listOf( 1, 2, 3 ) );
		$this->assertSame( '[1,2,3]', $json );
	}

	// ===============================================================
	// Helper functions
	// ===============================================================

	public function testListOfHelper(): void {
		$list = listOf( 'a', 'b' );
		$this->assertInstanceOf( Klist::class, $list );
		$this->assertSame( [ 'a', 'b' ], $list->toArray() );
	}

	public function testListFromHelper(): void {
		$list = listFrom( [ 'x', 'y' ] );
		$this->assertInstanceOf( Klist::class, $list );
		$this->assertSame( [ 'x', 'y' ], $list->toArray() );
	}

	public function testEmptyListHelper(): void {
		$list = emptyList();
		$this->assertInstanceOf( Klist::class, $list );
		$this->assertTrue( $list->isEmpty() );
	}

	public function testMutableListOfHelper(): void {
		$list = mutableListOf( 1, 2 );
		$list->add( 3 );
		$this->assertSame( [ 1, 2, 3 ], $list->toArray() );
	}

	public function testMutableListFromHelper(): void {
		$list = mutableListFrom( [ 'a', 'b' ] );
		$list->add( 'c' );
		$this->assertSame( [ 'a', 'b', 'c' ], $list->toArray() );
	}

	public function testMutableEmptyListHelper(): void {
		$list = mutableEmptyList();
		$this->assertTrue( $list->isEmpty() );
		$list->add( 1 );
		$this->assertSame( [ 1 ], $list->toArray() );
	}

	// ===============================================================
	// isEmpty short-circuit with fuseable pipeline
	// ===============================================================

	public function testIsEmptyWithFuseablePipeline(): void {
		$this->assertTrue(
			listOf( 1, 2, 3 )->filter( fn( $v ) => $v > 100 )->isEmpty()
		);
		$this->assertFalse(
			listOf( 1, 2, 3 )->filter( fn( $v ) => $v > 1 )->isEmpty()
		);
	}
}

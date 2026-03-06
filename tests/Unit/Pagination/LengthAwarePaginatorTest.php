<?php

namespace WpMVC\Database\Tests\Unit\Pagination;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Pagination\LengthAwarePaginator;
use WpMVC\Database\Eloquent\Collection;

class LengthAwarePaginatorTest extends TestCase {

    // =========================================================
    // Basic accessors
    // =========================================================

    public function test_it_calculates_total_pages() {
        $paginator = new LengthAwarePaginator( ['item1', 'item2'], 20, 5, 1 );
        $this->assertEquals( 4, $paginator->last_page() );

        $paginator = new LengthAwarePaginator( [], 0, 5, 1 );
        $this->assertEquals( 1, $paginator->last_page() );
    }

    public function test_it_knows_current_and_per_page() {
        $paginator = new LengthAwarePaginator( ['item1'], 10, 5, 2 );
        $this->assertEquals( 2, $paginator->current_page() );
        $this->assertEquals( 5, $paginator->per_page() );
        $this->assertEquals( 10, $paginator->total() );
    }

    public function test_it_serializes_to_array() {
        $items     = ['a', 'b'];
        $paginator = new LengthAwarePaginator( $items, 10, 2, 2 );
        $array     = $paginator->to_array();

        $this->assertEquals( 2, $array['current_page'] );
        $this->assertEquals( $items, $array['items'] );
        $this->assertEquals( 10, $array['total'] );
        $this->assertEquals( 2, $array['per_page'] );
        $this->assertEquals( 5, $array['last_page'] );
    }

    public function test_paginator_is_countable_and_iterable() {
        $items     = ['a', 'b', 'c'];
        $paginator = new LengthAwarePaginator( $items, 10, 5, 1 );

        $this->assertCount( 3, $paginator );
        $this->assertEquals( $items, $paginator->items()->all() );
    }

    // =========================================================
    // Edge cases
    // =========================================================

    public function test_it_defaults_to_page_one_when_not_set() {
        $paginator = new LengthAwarePaginator( [], 100, 10 );
        $this->assertEquals( 1, $paginator->current_page() );
    }

    public function test_last_page_rounds_up_for_partial_page() {
        $paginator = new LengthAwarePaginator( [], 101, 10 );
        $this->assertEquals( 11, $paginator->last_page() );
    }

    public function test_it_handles_exact_multiple_total() {
        $paginator = new LengthAwarePaginator( [], 30, 10 );
        $this->assertEquals( 3, $paginator->last_page() );
    }

    // =========================================================
    // Items / wrapping
    // =========================================================

    public function test_it_wraps_array_items_in_collection() {
        $paginator = new LengthAwarePaginator( ['a', 'b', 'c'], 3, 10 );
        $this->assertInstanceOf( Collection::class, $paginator->items() );
        $this->assertCount( 3, $paginator->items() );
    }

    public function test_it_accepts_collection_directly() {
        $collection = new Collection( ['x', 'y'] );
        $paginator  = new LengthAwarePaginator( $collection, 2, 10 );
        $this->assertInstanceOf( Collection::class, $paginator->items() );
        $this->assertCount( 2, $paginator->items() );
    }

    // =========================================================
    // Array access
    // =========================================================

    public function test_it_supports_array_access_read() {
        $paginator = new LengthAwarePaginator( ['first', 'second'], 2, 10 );

        $this->assertTrue( isset( $paginator[0] ) );
        $this->assertEquals( 'first', $paginator[0] );
        $this->assertEquals( 'second', $paginator[1] );
    }

    public function test_it_can_set_item_via_offset() {
        $paginator    = new LengthAwarePaginator( ['a', 'b'], 2, 10 );
        $paginator[0] = 'z';
        $this->assertEquals( 'z', $paginator[0] );
    }

    public function test_it_can_unset_item_via_offset() {
        $paginator = new LengthAwarePaginator( ['a', 'b'], 2, 10 );
        unset( $paginator[0] );
        $this->assertFalse( isset( $paginator[0] ) );
    }

    // =========================================================
    // JSON
    // =========================================================

    public function test_json_serialize_returns_same_as_to_array() {
        $paginator = new LengthAwarePaginator( [1, 2, 3], 30, 10, 1 );
        $this->assertEquals( $paginator->to_array(), $paginator->jsonSerialize() );
    }

    public function test_json_encode_produces_valid_json() {
        $paginator = new LengthAwarePaginator( ['a', 'b'], 20, 10, 1 );
        $json      = json_encode( $paginator );
        $decoded   = json_decode( $json, true );

        $this->assertIsArray( $decoded );
        $this->assertEquals( 1, $decoded['current_page'] );
        $this->assertEquals( 20, $decoded['total'] );
    }

    // =========================================================
    // Collection Proxy Methods
    // =========================================================

    public function test_it_proxies_each() {
        $paginator = new LengthAwarePaginator( [1, 2, 3], 3, 10 );
        $count     = 0;
        $paginator->each(
            function( $item ) use ( &$count ) {
                $count += $item;
            }
        );
        $this->assertEquals( 6, $count );
    }

    public function test_it_proxies_map_and_returns_paginator() {
        $paginator = new LengthAwarePaginator( [1, 2, 3], 30, 10, 1 );
        $mapped    = $paginator->map(
            function( $item ) {
                return $item * 2;
            }
        );

        $this->assertInstanceOf( LengthAwarePaginator::class, $mapped );
        $this->assertEquals( [2, 4, 6], $mapped->items()->all() );
        $this->assertEquals( 30, $mapped->total() );
        $this->assertEquals( 1, $mapped->current_page() );
    }

    public function test_it_proxies_filter_and_returns_paginator() {
        $paginator = new LengthAwarePaginator( [1, 2, 3], 3, 10 );
        $filtered  = $paginator->filter(
            function( $item ) {
                return $item > 1;
            }
        );

        $this->assertInstanceOf( LengthAwarePaginator::class, $filtered );
        $this->assertCount( 2, $filtered );
    }

    public function test_it_proxies_pluck() {
        $paginator = new LengthAwarePaginator( [['id' => 1, 'v' => 'a'], ['id' => 2, 'v' => 'b']], 2, 10 );
        $plucked   = $paginator->pluck( 'v' );

        $this->assertInstanceOf( Collection::class, $plucked );
        $this->assertEquals( ['a', 'b'], $plucked->all() );
    }

    public function test_it_proxies_where() {
        $paginator = new LengthAwarePaginator( [['id' => 1], ['id' => 2]], 2, 10 );
        $where     = $paginator->where( 'id', 2 );

        $this->assertInstanceOf( LengthAwarePaginator::class, $where );
        $this->assertCount( 1, $where );
        $this->assertEquals( 2, $where->first()['id'] );
    }

    public function test_it_proxies_unique() {
        $paginator = new LengthAwarePaginator( [1, 1, 2], 3, 10 );
        $unique    = $paginator->unique();

        $this->assertInstanceOf( LengthAwarePaginator::class, $unique );
        $this->assertCount( 2, $unique );
    }

    public function test_it_proxies_group_by() {
        $paginator = new LengthAwarePaginator( [['type' => 'a'], ['type' => 'b'], ['type' => 'a']], 3, 10 );
        $grouped   = $paginator->group_by( 'type' );

        $this->assertInstanceOf( Collection::class, $grouped );
        $this->assertCount( 2, $grouped );
        $this->assertCount( 2, $grouped['a'] );
    }

    public function test_it_proxies_sort_by() {
        $paginator = new LengthAwarePaginator( [3, 1, 2], 3, 10 );
        $sorted    = $paginator->sort_by(
            function( $item ) {
                return $item;
            }
        );

        $this->assertEquals( [1, 2, 3], array_values( $sorted->items()->all() ) );
    }

    public function test_it_proxies_reduce() {
        $paginator = new LengthAwarePaginator( [1, 2, 3], 3, 10 );
        $sum       = $paginator->reduce(
            function( $carry, $item ) {
                return $carry + $item;
            }, 0
        );

        $this->assertEquals( 6, $sum );
    }

    public function test_it_proxies_contains() {
        $paginator = new LengthAwarePaginator( ['a', 'b'], 2, 10 );
        $this->assertTrue( $paginator->contains( 'a' ) );
        $this->assertFalse( $paginator->contains( 'c' ) );
    }

    public function test_it_proxies_first_and_last() {
        $paginator = new LengthAwarePaginator( ['a', 'b', 'c'], 3, 10 );
        $this->assertEquals( 'a', $paginator->first() );
        $this->assertEquals( 'c', $paginator->last() );
    }

    public function test_it_proxies_is_empty_and_is_not_empty() {
        $paginator = new LengthAwarePaginator( [], 0, 10 );
        $this->assertTrue( $paginator->is_empty() );
        $this->assertFalse( $paginator->is_not_empty() );

        $paginator = new LengthAwarePaginator( [1], 1, 10 );
        $this->assertFalse( $paginator->is_empty() );
        $this->assertTrue( $paginator->is_not_empty() );
    }

    public function test_it_can_set_items() {
        $paginator = new LengthAwarePaginator( [1, 2], 10, 5, 1 );
        $paginator->set_items( [3, 4] );
        $this->assertEquals( [3, 4], $paginator->items()->all() );
    }
}

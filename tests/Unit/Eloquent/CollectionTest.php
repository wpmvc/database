<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Eloquent\Collection;
use Mockery;

class CollectionTest extends TestCase {
    public function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_filter_items() {
        $collection = new Collection( [1, 2, 3, 4, 5] );
        $filtered   = $collection->filter(
            function( $item ) {
                return $item > 2;
            }
        );

        $this->assertCount( 3, $filtered );
        $this->assertEquals( [3, 4, 5], array_values( $filtered->all() ) );
    }

    public function test_it_can_map_items() {
        $collection = new Collection( [1, 2, 3] );
        $mapped     = $collection->map(
            function( $item ) {
                return $item * 2;
            }
        );

        $this->assertEquals( [2, 4, 6], $mapped->all() );
    }

    public function test_it_can_pluck_attributes() {
        $model1       = Mockery::mock( 'Model' );
        $model1->name = 'Alice';
        
        $model2       = Mockery::mock( 'Model' );
        $model2->name = 'Bob';
        
        $collection = new Collection( [$model1, $model2] );
        $plucked    = $collection->pluck( 'name' );

        $this->assertEquals( ['Alice', 'Bob'], $plucked->all() );
    }

    public function test_it_can_get_unique_items() {
        $collection = new Collection( [1, 2, 2, 3, 3, 3] );
        $unique     = $collection->unique();

        $this->assertCount( 3, $unique );
        $this->assertEquals( [1, 2, 3], array_values( $unique->all() ) );
    }

    public function test_it_can_check_if_empty() {
        $collection = new Collection( [] );
        $this->assertEquals( 0, $collection->count() );
        
        $collection = new Collection( [1] );
        $this->assertNotEquals( 0, $collection->count() );
    }

    public function test_it_can_get_first_item() {
        $collection = new Collection( ['first', 'second'] );
        $this->assertEquals( 'first', $collection->first() );
    }

    public function test_it_is_countable_and_iterable() {
        $collection = new Collection( [1, 2, 3] );
        $this->assertCount( 3, $collection );
        
        $items = [];
        foreach ( $collection as $item ) {
            $items[] = $item;
        }
        $this->assertEquals( [1, 2, 3], $items );
    }

    public function test_it_can_reduce_to_scalar(): void {
        $collection = new Collection( [1, 2, 3, 4, 5] );
        $sum        = $collection->reduce(
            function( $carry, $item ) {
                return $carry + $item;
            }, 0
        );

        $this->assertEquals( 15, $sum );
    }

    public function test_first_with_callable_finds_first_match(): void {
        $collection = new Collection( [1, 5, 3, 7, 2] );
        $first_big  = $collection->first(
            function( $item ) {
                return $item > 4;
            }
        );

        $this->assertEquals( 5, $first_big );
    }

    public function test_first_with_callable_returns_null_when_no_match(): void {
        $collection = new Collection( [1, 2, 3] );
        $result     = $collection->first(
            function( $item ) {
                return $item > 100;
            }
        );

        $this->assertNull( $result );
    }

    public function test_it_can_chunk_into_sub_collections(): void {
        $collection = new Collection( [1, 2, 3, 4, 5] );
        $chunks     = $collection->chunk( 2 );

        $this->assertCount( 3, $chunks );          // [1,2] [3,4] [5]
        $this->assertCount( 2, $chunks->first() );
        $this->assertEquals( [1, 2], $chunks->first()->all() );
    }

    public function test_contains_value_returns_correct_boolean(): void {
        $collection = new Collection( ['Alice', 'Bob', 'Charlie'] );

        $this->assertTrue( $collection->contains( 'Alice' ) );
        $this->assertFalse( $collection->contains( 'Dave' ) );
    }
}

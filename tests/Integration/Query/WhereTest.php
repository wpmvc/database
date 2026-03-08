<?php

namespace WpMVC\Database\Tests\Integration\Query;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\Framework\Models\TestUser;

class WhereTest extends TestCase {
    public function set_up(): void {
        parent::set_up();
        
        Schema::drop_if_exists( 'where_test' );
        Schema::create(
            'where_test', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->integer( 'votes' )->nullable();
                $table->timestamps();
            } 
        );
    }

    public function tear_down(): void {
        Schema::drop_if_exists( 'where_test' );
        parent::tear_down();
    }

    protected function new_builder() {
        $builder = new Builder( new TestUser() );
        $builder->from( 'where_test' );
        return $builder;
    }

    /** @test */
    public function test_it_can_handle_complex_wheres() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => 20],
                ['name' => 'Charlie', 'votes' => 30],
                ['name' => 'David', 'votes' => 40],
            ] 
        );

        // Multiple WHERE
        $results = $this->new_builder()
            ->where( 'votes', '>', 15 )
            ->where( 'votes', '<', 35 )
            ->get();
        $this->assertCount( 2, $results ); // Bob and Charlie

        // OR WHERE
        $results = $this->new_builder()
            ->where( 'name', 'Alice' )
            ->or_where( 'name', 'David' )
            ->get();
        $this->assertCount( 2, $results );
    }

    /** @test */
    public function test_it_can_use_where_in() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => 20],
                ['name' => 'Charlie', 'votes' => 30],
            ] 
        );

        $results = $this->new_builder()->where_in( 'name', ['Alice', 'Charlie'] )->get();
        $this->assertCount( 2, $results );
        
        $results = $this->new_builder()->where_not_in( 'name', ['Bob'] )->get();
        $this->assertCount( 2, $results );
    }

    /** @test */
    public function test_it_can_use_where_between() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => 20],
                ['name' => 'Charlie', 'votes' => 30],
            ] 
        );

        $results = $this->new_builder()->where_between( 'votes', [15, 25] )->get();
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Bob', $results[0]->name );

        $results = $this->new_builder()->where_not_between( 'votes', [15, 25] )->get();
        $this->assertCount( 2, $results );
    }

    /** @test */
    public function test_it_can_use_where_null() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => null],
            ] 
        );

        $results = $this->new_builder()->where_null( 'votes' )->get();
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Bob', $results[0]->name );

        $results = $this->new_builder()->where_not_null( 'votes' )->get();
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Alice', $results[0]->name );
    }

    /** @test */
    public function test_it_can_use_where_raw() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => 20],
            ] 
        );

        $results = $this->new_builder()
            ->where_raw( 'votes % 3 = %d', [2] )
            ->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'Bob', $results[0]->name );
    }

    /** @test */
    public function test_it_can_use_subqueries_in_where_clauses() {
        Schema::create(
            'where_sub_test', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->integer( 'votes' );
            } 
        );

        $this->new_builder()->insert( ['name' => 'Expensive', 'votes' => 100] );
        $this->new_builder()->insert( ['name' => 'Cheap', 'votes' => 10] );

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'where_sub_test', ['votes' => 100] );

        $subquery = ( new Builder( new TestUser() ) )->from( 'where_sub_test' )->select( 'votes' );
        
        $items = $this->new_builder()
            ->where_in( 'votes', $subquery )
            ->get();

        $this->assertCount( 1, $items );
        $this->assertEquals( 'Expensive', $items[0]->name );

        Schema::drop_if_exists( 'where_sub_test' );
    }
}

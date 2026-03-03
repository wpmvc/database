<?php

namespace WpMVC\Database\Tests\Integration\Query;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\Framework\Models\TestUser;

class BuilderTest extends TestCase {
    public function set_up(): void {
        parent::set_up();
        
        Schema::drop_if_exists( 'builder_test' );
        Schema::create(
            'builder_test', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->integer( 'votes' )->default( 0 );
                $table->timestamps();
            } 
        );
    }

    public function tear_down(): void {
        Schema::drop_if_exists( 'builder_test' );
        parent::tear_down();
    }

    protected function new_builder() {
        $builder = new Builder( new TestUser() );
        $builder->from( 'builder_test' );
        return $builder;
    }

    public function test_it_can_insert_and_retrieve_records() {
        $builder = $this->new_builder();
        $builder->insert( ['name' => 'John Doe', 'votes' => 10] );

        $user = $this->new_builder()->where( 'name', 'John Doe' )->first();

        $this->assertNotNull( $user );
        $this->assertEquals( 'John Doe', $user->name );
        $this->assertEquals( 10, $user->votes );
    }

    public function test_it_can_update_records() {
        $this->new_builder()->insert( ['name' => 'Alice', 'votes' => 10] );

        $this->new_builder()->where( 'name', 'Alice' )->update( ['votes' => 100] );

        $alice = $this->new_builder()->where( 'name', 'Alice' )->first();
        $this->assertEquals( 100, $alice->votes );
    }

    public function test_it_can_delete_records() {
        $this->new_builder()->insert( ['name' => 'Alice', 'votes' => 10] );

        $this->new_builder()->where( 'name', 'Alice' )->delete();

        $this->assertNull( $this->new_builder()->where( 'name', 'Alice' )->first() );
    }

    public function test_it_can_order_results() {
        $this->new_builder()->insert(
            [
                ['name' => 'Alice', 'votes' => 30],
                ['name' => 'Bob', 'votes' => 10],
                ['name' => 'Charlie', 'votes' => 20],
            ] 
        );

        $results = $this->new_builder()->order_by( 'votes', 'asc' )->get();
        $this->assertEquals( 'Bob', $results[0]->name );
        $this->assertEquals( 'Alice', $results[2]->name );
    }

    public function test_it_can_paginate_results() {
        foreach ( range( 1, 10 ) as $i ) {
            $this->new_builder()->insert( ['name' => "User $i", 'votes' => $i] );
        }

        $page1 = $this->new_builder()->limit( 5 )->get();
        $this->assertCount( 5, $page1 );
        $this->assertEquals( 'User 1', $page1[0]->name );

        $page2 = $this->new_builder()->limit( 5 )->offset( 5 )->get();
        $this->assertCount( 5, $page2 );
        $this->assertEquals( 'User 6', $page2[0]->name );
    }
}

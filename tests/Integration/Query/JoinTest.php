<?php

namespace WpMVC\Database\Tests\Integration\Query;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\Framework\Models\TestUser;

class JoinTest extends TestCase {
    public function set_up(): void {
        parent::set_up();
        
        Schema::drop_if_exists( 'join_test_a' );
        Schema::drop_if_exists( 'join_test_b' );
        
        Schema::create(
            'join_test_a', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
            } 
        );

        Schema::create(
            'join_test_b', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'a_id' );
                $table->string( 'value' );
            } 
        );
    }

    public function tear_down(): void {
        Schema::drop_if_exists( 'join_test_a' );
        Schema::drop_if_exists( 'join_test_b' );
        parent::tear_down();
    }

    protected function new_builder() {
        return new Builder( new TestUser() );
    }

    /** @test */
    public function test_it_can_perform_inner_joins() {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'join_test_a', ['id' => 1, 'name' => 'A'] );
        $wpdb->insert( $wpdb->prefix . 'join_test_b', ['a_id' => 1, 'value' => 'B1'] );

        $results = $this->new_builder()
            ->from( 'join_test_a' )
            ->join( 'join_test_b', 'join_test_a.id', '=', 'join_test_b.a_id' )
            ->select( 'join_test_a.name', 'join_test_b.value' )
            ->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'A', $results[0]->name );
        $this->assertEquals( 'B1', $results[0]->value );
    }

    /** @test */
    public function test_it_can_perform_left_joins() {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'join_test_a', ['id' => 1, 'name' => 'A'] );
        $wpdb->insert( $wpdb->prefix . 'join_test_a', ['id' => 2, 'name' => 'B'] );
        $wpdb->insert( $wpdb->prefix . 'join_test_b', ['a_id' => 1, 'value' => 'V1'] );

        $results = $this->new_builder()
            ->from( 'join_test_a' )
            ->left_join( 'join_test_b', 'join_test_a.id', '=', 'join_test_b.a_id' )
            ->order_by( 'join_test_a.id' )
            ->get();

        $this->assertCount( 2, $results );
        $this->assertEquals( 'V1', $results[0]->value );
        $this->assertNull( $results[1]->value );
    }

    /** @test */
    public function test_it_can_perform_complex_joins_with_callbacks() {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'join_test_a', ['id' => 1, 'name' => 'A'] );
        $wpdb->insert( $wpdb->prefix . 'join_test_b', ['a_id' => 1, 'value' => 'V1'] );

        $results = $this->new_builder()
            ->from( 'join_test_a' )
            ->join(
                'join_test_b', function( $join ) {
                    $join->on( 'join_test_a.id', '=', 'join_test_b.a_id' )
                     ->where( 'join_test_b.value', '!=', 'ignored' );
                }
            )
            ->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'V1', $results[0]->value );
    }

    /** @test */
    public function test_it_handles_complex_joins_with_multiple_on_conditions() {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'join_test_a', ['id' => 1, 'name' => 'Alpha'] );
        $wpdb->insert( $wpdb->prefix . 'join_test_b', ['id' => 2, 'a_id' => 1, 'value' => 'Beta'] );

        $results = $this->new_builder()
            ->from( 'join_test_a' )
            ->join(
                'join_test_b', function( $join ) {
                     $join->on( 'join_test_a.id', '!=', 'join_test_b.id' )
                      ->on( 'join_test_a.id', '=', 'join_test_b.a_id' );
                } 
            )
            ->get();
        
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Alpha', $results[0]->name );
        $this->assertEquals( 'Beta', $results[0]->value );
    }
}

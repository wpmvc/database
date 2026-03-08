<?php

namespace WpMVC\Database\Tests\Integration\Query;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestPost;

class AggregateTest extends TestCase {
    public function set_up(): void {
        parent::set_up();
        
        Schema::drop_if_exists( 'aggregate_test' );
        Schema::create(
            'aggregate_test', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->integer( 'votes' );
            } 
        );
    }

    public function tear_down(): void {
        Schema::drop_if_exists( 'aggregate_test' );
        parent::tear_down();
    }

    protected function new_builder() {
        $builder = new Builder( new TestUser() );
        $builder->from( 'aggregate_test' );
        return $builder;
    }

    /** @test */
    public function test_it_can_count_records() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => 20],
                ['name' => 'Charlie', 'votes' => 30],
            ] 
        );

        $this->assertEquals( 3, $this->new_builder()->count() );
        $this->assertEquals( 1, $this->new_builder()->where( 'votes', '>', 25 )->count() );
    }

    /** @test */
    public function test_it_can_perform_aggregates() {
        $builder = $this->new_builder();
        $builder->insert(
            [
                ['name' => 'Alice', 'votes' => 10],
                ['name' => 'Bob', 'votes' => 20],
                ['name' => 'Charlie', 'votes' => 30],
            ] 
        );

        $this->assertEquals( 60, $this->new_builder()->sum( 'votes' ) );
        $this->assertEquals( 20, $this->new_builder()->avg( 'votes' ) );
        $this->assertEquals( 10, $this->new_builder()->min( 'votes' ) );
        $this->assertEquals( 30, $this->new_builder()->max( 'votes' ) );
    }
}

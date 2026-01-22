<?php

namespace WpMVC\Database\Tests\Unit\Query;

use Mockery;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Tests\TestCase;

class BuilderAggregateTest extends TestCase {
    protected $model;

    protected function setUp(): void {
        parent::setUp();
        $this->model = Mockery::mock( Model::class );
        $this->model->shouldReceive( 'get_table_name' )->andReturn( 'posts' );
        $this->model->shouldReceive( 'resolver' )->andReturn( new \WpMVC\Database\Resolver() );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for COUNT aggregate.
     */
    public function it_can_generate_count_sql() {
        $builder = new Builder( $this->model );
        
        // aggregate_to_sql is a helper to see what the SQL looks like without running it
        $sql = $builder->from( 'posts' )->aggregate_to_sql( 'count' );
        
        $this->assertEquals( 'select count(*) as aggregate from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for COUNT aggregate on a specific column.
     */
    public function it_can_generate_count_sql_with_specific_column() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->aggregate_to_sql( 'count', ['id'] );
        
        $this->assertEquals( 'select count(id) as aggregate from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for MAX aggregate.
     */
    public function it_can_generate_max_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->aggregate_to_sql( 'max', ['price'] );
        
        $this->assertEquals( 'select max(price) as aggregate from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for MIN aggregate.
     */
    public function it_can_generate_min_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->aggregate_to_sql( 'min', ['price'] );
        
        $this->assertEquals( 'select min(price) as aggregate from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for SUM aggregate.
     */
    public function it_can_generate_sum_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->aggregate_to_sql( 'sum', ['total'] );
        
        $this->assertEquals( 'select sum(total) as aggregate from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for AVG aggregate.
     */
    public function it_can_generate_avg_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->aggregate_to_sql( 'avg', ['rating'] );
        
        $this->assertEquals( 'select avg(rating) as aggregate from wp_posts as posts', $sql );
    }
}

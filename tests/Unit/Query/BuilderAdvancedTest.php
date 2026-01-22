<?php

namespace WpMVC\Database\Tests\Unit\Query;

use Mockery;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Tests\TestCase;

class BuilderAdvancedTest extends TestCase {
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
     * Verifies that the builder can generate a SELECT DISTINCT query.
     */
    public function it_can_generate_distinct_select() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->distinct()->select( 'title' )->to_sql();
        
        $this->assertEquals( 'select distinct title from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add HAVING clauses to the query.
     */
    public function it_can_add_having_clause() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->group_by( 'author_id' )
            ->having( 'count(id)', '>', 10 )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts group by author_id having count(id) > %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add OR HAVING clauses to the query.
     */
    public function it_can_add_or_having_clause() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->group_by( 'author_id' )
            ->having( 'count(id)', '>', 10 )
            ->or_having( 'sum(views)', '<', 500 )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts group by author_id having count(id) > %d or sum(views) < %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add ORDER BY DESC clauses to the query.
     */
    public function it_can_order_by_desc() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->order_by_desc( 'created_at' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts order by created_at desc', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add raw ORDER BY clauses to the query.
     */
    public function it_can_order_by_raw() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->order_by_raw( 'FIELD(status, "draft", "publish")' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts order by FIELD(status, "draft", "publish") ', $sql );
    }

    /**
     * @test
     * 
     * Verifies that an exception is thrown when an invalid order direction is provided.
     */
    public function it_throws_exception_on_invalid_order_direction() {
        $this->expectException( \InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Order direction must be "asc" or "desc".' );

        $builder = new Builder( $this->model );
        $builder->order_by( 'title', 'invalid' );
    }
}

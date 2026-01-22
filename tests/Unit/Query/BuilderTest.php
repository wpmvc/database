<?php

namespace WpMVC\Database\Tests\Unit\Query;

use Mockery;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Tests\TestCase;

class BuilderTest extends TestCase {
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
     * Verifies that the builder can generate a basic select query.
     */
    public function it_can_generate_basic_select_query() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->select( '*' )->from( 'posts' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate a select query with specific columns.
     */
    public function it_can_generate_select_with_specific_columns() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->select( ['id', 'title'] )->from( 'posts' )->to_sql();
        
        $this->assertEquals( 'select id, title from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE clauses to the query.
     */
    public function it_can_add_where_clauses() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->where( 'status', 'publish' )
            ->where( 'author_id', 1 )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts where status = %s and author_id = %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add OR WHERE clauses to the query.
     */
    public function it_can_add_or_where_clauses() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->where( 'status', 'publish' )
            ->or_where( 'status', 'draft' )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts where status = %s or status = %s', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE IN clauses to the query.
     */
    public function it_can_add_where_in_clauses() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->where_in( 'id', [1, 2, 3] )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts where id in (%d, %d, %d)', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add JOIN clauses to the query.
     */
    public function it_can_add_joins() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->join( 'users', 'posts.author_id', '=', 'users.id' )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts inner join wp_users as users on posts.author_id = users.id', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add LEFT JOIN clauses to the query.
     */
    public function it_can_add_left_joins() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->left_join( 'comments', 'posts.id', '=', 'comments.post_id' )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts left join wp_comments as comments on posts.id = comments.post_id', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add ORDER BY clauses to the query.
     */
    public function it_can_add_order_by() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->order_by( 'created_at', 'desc' )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts order by created_at desc', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add LIMIT and OFFSET clauses to the query.
     */
    public function it_can_add_limit_and_offset() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->limit( 10 )
            ->offset( 5 )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts limit %d offset %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can group results by specific columns.
     */
    public function it_can_group_by_columns() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->group_by( 'status' )
            ->to_sql();
            
        $this->assertEquals( 'select * from wp_posts as posts group by status', $sql );
    }
}

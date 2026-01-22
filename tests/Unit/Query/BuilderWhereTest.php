<?php

namespace WpMVC\Database\Tests\Unit\Query;

use Mockery;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Tests\TestCase;

class BuilderWhereTest extends TestCase {
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
     * Verifies that the builder can add WHERE NOT clauses to the query.
     */
    public function it_can_add_where_not() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_not( 'status', 'draft' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where not status = %s', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE NULL clauses to the query.
     */
    public function it_can_add_where_null() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_null( 'deleted_at' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where deleted_at is null', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE NOT NULL clauses to the query.
     */
    public function it_can_add_where_not_null() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_not_null( 'published_at' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where published_at is not null', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE BETWEEN clauses to the query.
     */
    public function it_can_add_where_between() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_between( 'views', [100, 200] )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where views between %d and %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE NOT BETWEEN clauses to the query.
     */
    public function it_can_add_where_not_between() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_not_between( 'views', [10, 20] )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where views not between %d and %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE LIKE clauses to the query.
     */
    public function it_can_add_where_like() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_like( 'title', '%Hello%' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where title like %s', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE NOT LIKE clauses to the query.
     */
    public function it_can_add_where_not_like() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_not_like( 'title', '%spam%' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where title not like %s', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add raw WHERE clauses to the query.
     */
    public function it_can_add_where_raw() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_raw( 'YEAR(created_at) > ?', [2020] )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where YEAR(created_at) > ?', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE COLUMN clauses to the query.
     */
    public function it_can_add_where_column() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_column( 'first_name', '=', 'last_name' )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where first_name = last_name', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE IN clauses to the query.
     */
    public function it_can_add_where_in() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_in( 'id', [1, 2] )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where id in (%d, %d)', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE NOT IN clauses to the query.
     */
    public function it_can_add_where_not_in() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_not_in( 'id', [9, 10] )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where id not in (%d, %d)', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can add WHERE EXISTS clauses to the query.
     */
    public function it_can_add_where_exists() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->where_exists(
            function ( $query ) {
                $query->from( 'comments' )->where_column( 'comments.post_id', 'posts.id' );
            } 
        )->to_sql();
        
        $this->assertEquals( 'select * from wp_posts as posts where exists (select * from wp_comments as comments where comments.post_id = posts.id)', $sql );
    }
}

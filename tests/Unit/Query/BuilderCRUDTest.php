<?php

namespace WpMVC\Database\Tests\Unit\Query;

use Mockery;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Tests\TestCase;

class BuilderCRUDTest extends TestCase {
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
     * Verifies that the builder can generate valid SQL for INSERT operations.
     */
    public function it_can_generate_insert_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )->to_sql_insert(
            [
                'title'  => 'New Post',
                'status' => 'draft'
            ] 
        );
        
        $this->assertEquals( "insert into wp_posts (title, status) values (%s, %s)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for UPDATE operations.
     */
    public function it_can_generate_update_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->where( 'id', 1 )
            ->to_sql_update(
                [
                    'status' => 'publish'
                ] 
            );
            
        $this->assertEquals( "update wp_posts set status = %s where id = %d", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the builder can generate valid SQL for DELETE operations.
     */
    public function it_can_generate_delete_sql() {
        $builder = new Builder( $this->model );
        
        $sql = $builder->from( 'posts' )
            ->where( 'status', 'spam' )
            ->to_sql_delete();
            
        $this->assertEquals( "delete posts from wp_posts as posts  where status = %s", $sql );
    }
}

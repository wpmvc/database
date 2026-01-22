<?php

namespace WpMVC\Database\Tests\Unit\Query\Compilers;

use Mockery;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Query\Compilers\Compiler;
use WpMVC\Database\Tests\TestCase;
use WpMVC\Database\Eloquent\Model;

class CompilerTest extends TestCase {
    protected $compiler;

    protected $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->compiler = new Compiler();
        
        $model = Mockery::mock( Model::class );
        $model->shouldReceive( 'get_table_name' )->andReturn( 'posts' );
        $model->shouldReceive( 'resolver' )->andReturn( new \WpMVC\Database\Resolver() );
        
        $this->builder = new Builder( $model );
        $this->builder->from( 'posts' );
    }

    /**
     * @test
     * 
     * Verifies that the compiler can compile a SELECT statement.
     */
    public function it_compiles_select_statement() {
        $this->builder->select( ['id', 'title'] );
        
        $sql = $this->compiler->compile_select( $this->builder );
        
        $this->assertEquals( 'select id, title from wp_posts as posts', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the compiler can compile an INSERT statement.
     */
    public function it_compiles_insert_statement() {
        $values = ['title' => 'Test', 'content' => 'Content'];
        
        $sql = $this->compiler->compile_insert( $this->builder, $values );
        
        $this->assertEquals( 'insert into wp_posts (title, content) values (%s, %s)', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the compiler can compile an UPDATE statement.
     */
    public function it_compiles_update_statement() {
        $this->builder->where( 'id', 1 );
        $values = ['status' => 'published'];
        
        $sql = $this->compiler->compile_update( $this->builder, $values );
        
        $this->assertEquals( 'update wp_posts set status = %s where id = %d', $sql );
    }

    /**
     * @test
     * 
     * Verifies that the compiler can compile a DELETE statement.
     */
    public function it_compiles_delete_statement() {
        $this->builder->where( 'id', 1 );
        
        $sql = $this->compiler->compile_delete( $this->builder );
        
        $this->assertEquals( 'delete posts from wp_posts as posts  where id = %d', $sql );
    }
}

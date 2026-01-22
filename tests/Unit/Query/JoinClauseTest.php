<?php

namespace WpMVC\Database\Tests\Unit\Query;

use Mockery;
use WpMVC\Database\Query\JoinClause;
use WpMVC\Database\Tests\TestCase;
use WpMVC\Database\Eloquent\Model;

class JoinClauseTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that a JoinClause instance can be created with the correct type.
     */
    public function it_creates_join_clause() {
        $model = Mockery::mock( Model::class );
        $model->shouldReceive( 'get_table_name' )->andReturn( 'posts' );
        $model->shouldReceive( 'resolver' )->andReturn( new \WpMVC\Database\Resolver() );
        
        $join = new JoinClause( 'users', 'inner', $model );
        
        $this->assertEquals( 'inner', $join->type );
    }

    /**
     * @test
     * 
     * Verifies that ON conditions can be added to a JoinClause.
     */
    public function it_can_add_on_conditions() {
        $model = Mockery::mock( Model::class );
        $model->shouldReceive( 'get_table_name' )->andReturn( 'posts' );
        $model->shouldReceive( 'resolver' )->andReturn( new \WpMVC\Database\Resolver() );

        $join = new JoinClause( 'users', 'inner', $model );
        
        $join->on( 'posts.author_id', '=', 'users.id' );
        
        $this->assertCount( 1, $join->get_ons() );
    }
}

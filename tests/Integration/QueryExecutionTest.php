<?php

namespace WpMVC\Database\Tests\Integration;

use stdClass;
use WpMVC\Database\Tests\Fixtures\Models\Post;

/**
 * Integration tests for query execution.
 * 
 * Tests verify that query builder generates correct SQL and
 * properly interacts with wpdb for data retrieval.
 */
class QueryExecutionTest extends IntegrationTestCase {
    /**
     * @test
     * 
     * Verifies that SELECT queries are executed and results are returned correctly.
     */
    public function it_executes_select_query_and_returns_results() {
        // Arrange
        $mock_post         = new stdClass();
        $mock_post->id     = 1;
        $mock_post->title  = 'Test Post';
        $mock_post->status = 'publish';
        
        $this->mockDatabaseResults( [$mock_post] );
        
        // Act
        $results = Post::query()->where( 'status', 'publish' )->get();
        
        // Assert
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Test Post', $results[0]->title );
        $this->assertQueryExecuted( "select * from wp_posts as posts where status = %s" );
    }

    /**
     * @test
     * 
     * Verifies that INSERT queries correctly execute and return insert ID.
     */
    public function it_executes_insert_query_and_returns_id() {
        // Arrange
        $data = [
            'title'  => 'New Post',
            'status' => 'draft'
        ];
        
        // Act
        $result = Post::query()->insert( $data );
        
        // Assert
        $this->assertTrue( (bool) $result );
        $this->assert_sql_contains( 'insert into wp_posts', $this->getLastQuery() );
        $this->assert_sql_contains( 'title', $this->getLastQuery() );
        $this->assert_sql_contains( 'status', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that UPDATE queries execute with WHERE clauses.
     */
    public function it_executes_update_query_with_where_clause() {
        // Arrange
        $updates = ['status' => 'publish'];
        
        // Act
        $result = Post::query()
            ->where( 'id', 1 )
            ->update( $updates );
        
        // Assert
        $this->assertTrue( (bool) $result );
        $this->assert_sql_contains( 'update wp_posts', $this->getLastQuery() );
        $this->assert_sql_contains( 'where id = %d', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that DELETE queries execute with proper conditions.
     */
    public function it_executes_delete_query_with_conditions() {
        // Act
        $result = Post::query()
            ->where( 'status', 'trash' )
            ->delete();
        
        // Assert
        $this->assertTrue( (bool) $result );
        $this->assert_sql_contains( 'delete', $this->getLastQuery() );
        $this->assert_sql_contains( 'where status', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that complex queries with multiple clauses work correctly.
     */
    public function it_executes_complex_query_with_multiple_clauses() {
        // Arrange
        $mock_posts = [
            (object) ['id' => 1, 'title' => 'Post 1'],
            (object) ['id' => 2, 'title' => 'Post 2']
        ];
        $this->mockDatabaseResults( $mock_posts );
        
        // Act
        $results = Post::query()
            ->where( 'status', 'publish' )
            ->where( 'author_id', 5 )
            ->order_by( 'created_at', 'desc' )
            ->limit( 10 )
            ->get();
        
        // Assert
        $this->assertCount( 2, $results );
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'where status', $last_query );
        $this->assert_sql_contains( 'and author_id', $last_query );
        $this->assert_sql_contains( 'order by created_at desc', $last_query );
        $this->assert_sql_contains( 'limit', $last_query );
    }

    /**
     * @test
     * 
     * Verifies that first() method returns single result.
     */
    public function it_returns_single_result_with_first() {
        // Arrange
        $mock_post        = new stdClass();
        $mock_post->id    = 1;
        $mock_post->title = 'First Post';
        
        $this->mockDatabaseResults( [$mock_post] );
        
        // Act
        $result = Post::query()->where( 'id', 1 )->first();
        
        // Assert
        $this->assertNotNull( $result );
        $this->assertEquals( 'First Post', $result->title );
        $this->assert_sql_contains( 'limit %d', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that aggregate queries (count) execute correctly.
     */
    public function it_executes_count_aggregate_query() {
        // Arrange
        $mock_result            = new stdClass();
        $mock_result->aggregate = 42;
        
        $this->mockDatabaseResults( [$mock_result] );
        
        // Act
        $count = Post::query()->where( 'status', 'publish' )->count();
        
        // Assert
        $this->assertEquals( 42, $count );
        $this->assert_sql_contains( 'count(*)', $this->getLastQuery() );
    }
}

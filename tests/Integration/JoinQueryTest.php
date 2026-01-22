<?php

namespace WpMVC\Database\Tests\Integration;

use stdClass;
use WpMVC\Database\Tests\Fixtures\Models\Post;

/**
 * Integration tests for JOIN queries.
 * 
 * Tests verify that query builder correctly constructs and executes
 * JOIN queries with various join types and conditions.
 */
class JoinQueryTest extends IntegrationTestCase {
    /**
     * @test
     * 
     * Verifies that INNER JOIN generates correct SQL.
     */
    public function it_executes_inner_join_query() {
        // Arrange
        $mock_results = [
            (object) ['id' => 1, 'title' => 'Post 1', 'author_name' => 'John']
        ];
        $this->mockDatabaseResults( $mock_results );
        
        // Act
        $results = Post::query()
            ->join( 'users', 'posts.author_id', '=', 'users.id' )
            ->select( ['posts.*', 'users.name as author_name'] )
            ->get();
        
        // Assert
        $this->assertCount( 1, $results );
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'inner join', $last_query );
        $this->assert_sql_contains( 'wp_users', $last_query );
        $this->assert_sql_contains( 'posts.author_id = users.id', $last_query );
    }

    /**
     * @test
     * 
     * Verifies that LEFT JOIN generates correct SQL.
     */
    public function it_executes_left_join_query() {
        // Arrange
        $mock_results = [
            (object) ['id' => 1, 'title' => 'Post 1', 'comment_count' => 5]
        ];
        $this->mockDatabaseResults( $mock_results );
        
        // Act
        $results = Post::query()
            ->left_join( 'comments', 'posts.id', '=', 'comments.post_id' )
            ->get();
        
        // Assert
        $this->assertCount( 1, $results );
        $this->assert_sql_contains( 'left join', $this->getLastQuery() );
        $this->assert_sql_contains( 'wp_comments', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that multiple JOINs can be chained.
     */
    public function it_executes_multiple_join_query() {
        // Arrange
        $mock_results = [];
        $this->mockDatabaseResults( $mock_results );
        
        // Act
        Post::query()
            ->join( 'users', 'posts.author_id', '=', 'users.id' )
            ->left_join( 'comments', 'posts.id', '=', 'comments.post_id' )
            ->get();
        
        // Assert
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'inner join wp_users', $last_query );
        $this->assert_sql_contains( 'left join wp_comments', $last_query );
    }

    /**
     * @test
     * 
     * Verifies that JOIN with WHERE clause works correctly.
     */
    public function it_executes_join_with_where_clause() {
        // Arrange
        $mock_results = [];
        $this->mockDatabaseResults( $mock_results );
        
        // Act
        Post::query()
            ->join( 'users', 'posts.author_id', '=', 'users.id' )
            ->where( 'posts.status', 'publish' )
            ->where( 'users.role', 'author' )
            ->get();
        
        // Assert
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'join', $last_query );
        $this->assert_sql_contains( 'where posts.status', $last_query );
        $this->assert_sql_contains( 'and users.role', $last_query );
    }

    /**
     * @test
     * 
     * Verifies that JOIN with complex ON conditions works.
     */
    public function it_executes_join_with_closure_conditions() {
        // Arrange
        $mock_results = [];
        $this->mockDatabaseResults( $mock_results );
        
        // Act
        Post::query()
            ->join(
                'users',
                function ( $join ) {
                    $join->on_column( 'posts.author_id', '=', 'users.id' )
                         ->where( 'users.status', 'active' );
                }
            )
            ->get();
        
        // Assert
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'join', $last_query );
        // Complex ON conditions are handled by JoinClause
    }
}

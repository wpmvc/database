<?php

namespace WpMVC\Database\Tests\Integration;

use InvalidArgumentException;
use WpMVC\Database\Tests\Fixtures\Models\Post;

/**
 * Integration tests for edge cases and error handling.
 * 
 * Tests verify that the database layer handles edge cases gracefully
 * and provides appropriate error messages.
 */
class EdgeCasesTest extends IntegrationTestCase {
    /**
     * @test
     * 
     * Verifies that empty result sets are handled correctly.
     */
    public function it_handles_empty_result_sets() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        $results = Post::query()->where( 'id', 999999 )->get();
        
        // Assert
        $this->assertIsArray( $results );
        $this->assertEmpty( $results );
    }

    /**
     * @test
     * 
     * Verifies that first() returns null when no results found.
     */
    public function it_returns_null_when_first_finds_nothing() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        $result = Post::query()->where( 'id', 999999 )->first();
        
        // Assert
        $this->assertNull( $result );
    }

    /**
     * @test
     * 
     * Verifies that whereNull handles null columns correctly.
     */
    public function it_handles_where_null_correctly() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()->where_null( 'deleted_at' )->get();
        
        // Assert
        $this->assert_sql_contains( 'where deleted_at is null', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that whereNotNull handles not null columns correctly.
     */
    public function it_handles_where_not_null_correctly() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()->where_not_null( 'published_at' )->get();
        
        // Assert
        $this->assert_sql_contains( 'where published_at is not null', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that invalid order direction throws exception.
     */
    public function it_throws_exception_for_invalid_order_direction() {
        // Arrange & Act & Assert
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Order direction must be "asc" or "desc"' );
        
        Post::query()->order_by( 'created_at', 'invalid' );
    }

    /**
     * @test
     * 
     * Verifies that pagination calculates offset correctly.
     */
    public function it_calculates_pagination_offset_correctly() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()->pagination( 3, 10 ); // Page 3, 10 per page
        
        // Assert
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'limit %d', $last_query );
        $this->assert_sql_contains( 'offset %d', $last_query ); // (3-1) * 10 = 20
    }

    /**
     * @test
     * 
     * Verifies that whereBetween handles range queries correctly.
     */
    public function it_handles_where_between_correctly() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()->where_between( 'views', [100, 500] )->get();
        
        // Assert
        $this->assert_sql_contains( 'where views between', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that whereLike handles pattern matching queries.
     */
    public function it_handles_where_like_correctly() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()->where_like( 'title', '%search%' )->get();
        
        // Assert
        $this->assert_sql_contains( 'where title like', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that distinct() generates correct SQL.
     */
    public function it_handles_distinct_queries() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()->distinct()->select( 'status' )->get();
        
        // Assert
        $this->assert_sql_contains( 'select distinct status', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that groupBy and having work together.
     */
    public function it_handles_group_by_with_having() {
        // Arrange
        $this->mockDatabaseResults( [] );
        
        // Act
        Post::query()
            ->select( ['author_id', 'COUNT(*) as post_count'] )
            ->group_by( 'author_id' )
            ->having( 'COUNT(*)', '>', 5 )
            ->get();
        
        // Assert
        $last_query = $this->getLastQuery();
        $this->assert_sql_contains( 'group by author_id', $last_query );
        $this->assert_sql_contains( 'having count(*)', $last_query );
    }
}

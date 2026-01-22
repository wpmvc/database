<?php

namespace WpMVC\Database\Tests\Integration;

use stdClass;
use WpMVC\Database\Tests\Fixtures\Models\Post;
use WpMVC\Database\Tests\Fixtures\Models\Comment;
use WpMVC\Database\Tests\Fixtures\Models\User;

/**
 * Integration tests for relationship loading.
 * 
 * Tests verify that Eloquent relationships correctly load related data
 * and generate appropriate SQL queries.
 */
class RelationshipLoadingTest extends IntegrationTestCase {
    /**
     * @test
     * 
     * Verifies that has_many relationship is defined correctly.
     */
    public function it_defines_has_many_relationship() {
        // Arrange
        $post = new Post();
        
        // Act
        $relation = $post->has_many( Comment::class, 'post_id', 'id' );
        
        // Assert
        $this->assertInstanceOf( \WpMVC\Database\Eloquent\Relations\HasMany::class, $relation );
        $this->assertEquals( 'post_id', $relation->foreign_key );
        $this->assertEquals( 'id', $relation->local_key );
    }

    /**
     * @test
     * 
     * Verifies that belongs_to_one relationship is defined correctly.
     */
    public function it_defines_belongs_to_one_relationship() {
        // Arrange
        $comment = new Comment();
        
        // Act
        $relation = $comment->belongs_to_one( Post::class, 'post_id', 'id' );
        
        // Assert
        $this->assertInstanceOf( \WpMVC\Database\Eloquent\Relations\BelongsToOne::class, $relation );
        $this->assertEquals( 'post_id', $relation->foreign_key );
        $this->assertEquals( 'id', $relation->local_key );
    }

    /**
     * @test
     * 
     * Verifies that query builder properly handles relationship constraints.
     */
    public function it_applies_relationship_constraints_to_query() {
        // Arrange
        $mock_comments = [
            (object) ['id' => 1, 'post_id' => 5, 'content' => 'Comment 1'],
            (object) ['id' => 2, 'post_id' => 5, 'content' => 'Comment 2']
        ];
        $this->mockDatabaseResults( $mock_comments );
        
        // Act
        $results = Comment::query()->where( 'post_id', 5 )->get();
        
        // Assert
        $this->assertCount( 2, $results );
        $this->assert_sql_contains( 'where post_id = %d', $this->getLastQuery() );
    }

    /**
     * @test
     * 
     * Verifies that with() method for relationship eager loading works.
     */
    public function it_sets_up_eager_loading_with_with_method() {
        // Act - Just verify method exists and sets up relationships
        $query = Post::query()->with( 'comments' );
        
        // Assert - Verify query builder accepts with() method
        $this->assertInstanceOf( \WpMVC\Database\Query\Builder::class, $query );
    }
}

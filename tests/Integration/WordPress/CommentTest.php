<?php
/**
 * Comment Integration Test
 *
 * @package WpMVC\Database\Tests\Integration\WordPress
 */

namespace WpMVC\Database\Tests\Integration\WordPress;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Integration\WordPress\Models\Comment;
use WpMVC\Database\Tests\Integration\WordPress\Models\CommentMeta;
use WpMVC\Database\Tests\Integration\WordPress\Models\Post;
use WpMVC\Database\Tests\Integration\WordPress\Models\User;

/**
 * Class CommentTest
 */
class CommentTest extends TestCase {
    /**
     * Test creating a comment.
     */
    public function test_it_can_create_a_comment() {
        $post = Post::create(
            [
                'post_title' => 'Commentable Post',
            ]
        );

        $comment = Comment::create(
            [
                'comment_post_ID' => $post->ID,
                'comment_author'  => 'John Doe',
                'comment_content' => 'Nice post!',
                'comment_date'    => '2026-02-27 13:00:00',
            ]
        );

        $this->assertIsInt( $comment->comment_ID );
        $this->assertEquals( 'John Doe', $comment->comment_author );
        $this->assertEquals( $post->ID, $comment->comment_post_ID );
    }

    /**
     * Test comment relationships.
     */
    public function test_it_has_relationships() {
        $user = User::create(
            [
                'user_login' => 'commenter',
                'user_email' => 'commenter@example.com',
            ]
        );

        $post = Post::create(
            [
                'post_title' => 'Rel Post',
            ]
        );

        $comment = Comment::create(
            [
                'comment_post_ID' => $post->ID,
                'user_id'         => $user->ID,
                'comment_content' => 'Relationship test',
            ]
        );

        $this->assertEquals( $post->ID, $comment->post->ID );
        $this->assertEquals( $user->ID, $comment->user->ID );
    }

    /**
     * Test comment meta.
     */
    public function test_it_can_handle_commentmeta() {
        $comment = Comment::create(
            [
                'comment_content' => 'Meta Test',
            ]
        );

        $comment->meta()->create(
            [
                'meta_key'   => 'rating',
                'meta_value' => '5',
            ]
        );

        $this->assertCount( 1, $comment->meta );
        $this->assertEquals( 'rating', $comment->meta->first()->meta_key );
        
        // Test inverse
        $meta = $comment->meta->first();
        $this->assertEquals( $comment->comment_ID, $meta->comment->comment_ID );
    }
}

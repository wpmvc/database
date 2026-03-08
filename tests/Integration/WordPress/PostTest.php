<?php
/**
 * Post Integration Test
 *
 * @package WpMVC\Database\Tests\Integration\WordPress
 */

namespace WpMVC\Database\Tests\Integration\WordPress;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Integration\WordPress\Models\Post;
use WpMVC\Database\Tests\Integration\WordPress\Models\PostMeta;
use WpMVC\Database\Tests\Integration\WordPress\Models\User;

/**
 * Class PostTest
 */
class PostTest extends TestCase {
    /**
     * Test creating a post.
     */
    public function test_it_can_create_a_post() {
        $user = User::create(
            [
                'user_login' => 'postauthor',
                'user_email' => 'author@example.com',
            ]
        );

        $post = Post::create(
            [
                'post_author'  => $user->ID,
                'post_title'   => 'Hello World',
                'post_content' => 'This is a test post.',
                'post_status'  => 'publish',
                'post_type'    => 'post',
                'post_date'    => '2026-02-27 12:00:00',
            ]
        );

        $this->assertIsInt( $post->ID );
        $this->assertEquals( 'Hello World', $post->post_title );
        $this->assertInstanceOf( 'DateTime', $post->post_date );
        $this->assertEquals( $user->ID, $post->post_author );
    }

    /**
     * Test post meta relationship.
     */
    public function test_it_can_handle_postmeta() {
        $post = Post::create(
            [
                'post_title' => 'Meta Test',
            ]
        );

        $post->meta()->create(
            [
                'meta_key'   => '_test_key',
                'meta_value' => 'test_value',
            ]
        );

        $this->assertCount( 1, $post->meta );
        $this->assertEquals( '_test_key', $post->meta->first()->meta_key );
        $this->assertEquals( 'test_value', $post->meta->first()->meta_value );

        // Test inverse relationship
        $meta = PostMeta::where( 'meta_key', '_test_key' )->first();
        $this->assertEquals( $post->ID, $meta->post->ID );
    }

    /**
     * Test author relationship.
     */
    public function test_it_can_fetch_author() {
        $user = User::create(
            [
                'user_login' => 'relauthor',
                'user_email' => 'rel@example.com',
            ]
        );

        $post = Post::create(
            [
                'post_author' => $user->ID,
                'post_title'  => 'Rel Test',
            ]
        );

        $this->assertEquals( $user->user_login, $post->author->user_login );
    }
}

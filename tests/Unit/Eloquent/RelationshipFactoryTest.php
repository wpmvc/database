<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Eloquent\Factory;
use WpMVC\Database\Tests\Framework\Mocks\User;
use WpMVC\Database\Tests\Framework\Mocks\Post;
use WpMVC\Database\Tests\Framework\Mocks\Comment;
use WpMVC\Database\Tests\Framework\Factories\UserFactory;

class PostFactory extends Factory {
    protected $model = Post::class;

    public function definition(): array {
        return [
            'post_title'   => $this->faker()->sentence(),
            'post_content' => $this->faker()->paragraph(),
            'post_author'  => UserFactory::new(),
        ];
    }
}

class CommentFactory extends Factory {
    protected $model = Comment::class;

    public function definition(): array {
        return [
            'comment_content'  => $this->faker()->paragraph(),
            'commentable_id'   => 1, // Default, usually overridden
            'commentable_type' => Post::class,
        ];
    }
}

class RelationshipFactoryTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        
        // Ensure tables exist before running tests
        global $wpdb;
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mock_users` (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_login varchar(60) NOT NULL,
            name varchar(255) DEFAULT '',
            email varchar(100) DEFAULT '',
            role varchar(50) DEFAULT 'user',
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        )" 
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mock_posts` (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_title varchar(255) NOT NULL,
            post_content text,
            post_author bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        )" 
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mock_comments` (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_content text NOT NULL,
            commentable_id bigint(20) unsigned DEFAULT NULL,
            commentable_type varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        )" 
        );

        User::unguard();
        Post::unguard();
        Comment::unguard();
    }

    public function test_it_can_create_with_belongs_to_relationship() {
        // Post definition uses UserFactory for post_author
        $post = PostFactory::new()->make();

        $this->assertNotNull( $post->post_author );
        $this->assertIsNumeric( $post->post_author );
    }

    public function test_it_can_create_with_has_many_relationship() {
        $user = UserFactory::new()
            ->has( PostFactory::new()->count( 3 ), 'posts' )
            ->create();

        // Access via magic property or get()
        $this->assertCount( 3, $user->posts );
        $this->assertEquals( $user->get_key(), $user->posts->first()->post_author );
    }

    public function test_it_can_recycle_models() {
        $user         = new User( ['id' => 123] );
        $user->exists = true;

        $post = PostFactory::new()
            ->recycle( $user )
            ->make();

        $this->assertEquals( 123, $post->post_author );
    }

    public function test_it_can_handle_polymorphic_relationships() {
        $post         = new Post( ['id' => 456] );
        $post->exists = true;

        $comment = CommentFactory::new()
            ->for( $post, 'commentable' )
            ->make();

        $this->assertEquals( 456, $comment->commentable_id );
        $this->assertEquals( Post::class, $comment->commentable_type );
    }

    public function test_it_can_handle_nested_creation() {
        // User -> Post -> Comment
        // We use create() to ensure the chain is saved and IDs are generated
        $comment = CommentFactory::new()
            ->for( PostFactory::new()->for( UserFactory::new(), 'user' ), 'commentable' )
            ->create();

        $this->assertNotNull( $comment->commentable_id );
        $this->assertEquals( Post::class, $comment->commentable_type );
        
        $post = Post::find( $comment->commentable_id );
        $this->assertNotNull( $post );
        $this->assertNotNull( $post->post_author );
    }

    public function test_it_can_use_magic_relationship_methods() {
        // Define the expected factory in the guessed namespace for the test
        if ( ! class_exists( 'WpMVC\\Database\\Tests\\Framework\\Mocks\\PostFactory' ) ) {
            eval(
                'namespace WpMVC\\Database\\Tests\\Framework\\Mocks; class PostFactory extends \\WpMVC\\Database\\Eloquent\\Factory { 
                protected $model = Post::class;
                public function definition(): array { return ["post_title" => "Magic Post"]; } 
            }' 
            );
        }
        if ( ! class_exists( 'WpMVC\\Database\\Tests\\Framework\\Mocks\\UserFactory' ) ) {
            eval(
                'namespace WpMVC\\Database\\Tests\\Framework\\Mocks; class UserFactory extends \\WpMVC\\Database\\Eloquent\\Factory { 
                protected $model = User::class;
                public function definition(): array { return ["user_login" => "magic_user"]; } 
            }' 
            );
        }

        $user = UserFactory::new()->hasPosts( 2 )->create();
        
        // Verify relationship was created and linked
        $this->assertCount( 2, $user->posts );
        $this->assertEquals( 'Magic Post', $user->posts->first()->post_title );
    }
}

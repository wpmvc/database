<?php

namespace WpMVC\Database\Tests\Integration\Multisite;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestPost;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestNetworkUser;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class MultisiteModelTest extends TestCase {
    public function set_up(): void {
        parent::set_up();
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'Multisite is not enabled.' );
        }
        
        if ( ! isset( $this->factory ) || ! isset( $this->factory->blog ) ) {
            $this->markTestSkipped( 'Multisite factory not available.' );
        }
    }

    public function tear_down(): void {
        global $wpdb;
        // Clean up tables in current prefix before restoring
        Schema::drop_if_exists( 'test_posts' );
        parent::tear_down();
    }

    public function test_models_resolve_table_name_after_blog_switch() {
        global $wpdb;
        
        // 1. Setup in main blog
        Schema::create(
            'test_posts', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'title' );
                $table->timestamps();
            } 
        );
        
        TestPost::create( ['title' => 'Main Blog Post'] );
        $this->assertEquals( 1, TestPost::count() );
        $this->assertEquals( $wpdb->prefix . 'test_posts', ( new TestPost() )->get_table_full_name() );

        // 2. Switch to new blog
        $blog_id = $this->factory->blog->create();
        switch_to_blog( $blog_id );
        
        // Verify prefix changed
        $this->assertStringContainsString( "_{$blog_id}_", $wpdb->prefix );
        
        // 3. Setup in second blog
        Schema::create(
            'test_posts', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'title' );
                $table->timestamps();
            } 
        );
        
        // Verify model sees the new table (should be empty)
        $this->assertEquals( 0, TestPost::count() );
        $this->assertEquals( $wpdb->prefix . 'test_posts', ( new TestPost() )->get_table_full_name() );
        
        TestPost::create( ['title' => 'New Blog Post'] );
        $this->assertEquals( 1, TestPost::count() );
        $this->assertEquals( 'New Blog Post', TestPost::first()->title );

        // 4. Verification in main blog again
        restore_current_blog();
        $this->assertEquals( 1, TestPost::count() );
        $this->assertEquals( 'Main Blog Post', TestPost::first()->title );
        
        // Clean up second blog table manually since tear_down only cleans current
        switch_to_blog( $blog_id );
        Schema::drop_if_exists( 'test_posts' );
        restore_current_blog();
    }

    public function test_it_handles_cross_blog_relationships() {
        global $wpdb;
        
        // 1. Setup tables
        Schema::drop_if_exists( 'test_users' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'users' );

        Schema::create(
            'test_users', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'user_login' );
                $table->timestamps();
            } 
        );
        
        Schema::create(
            'users', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'user_login' );
                $table->timestamps();
            } 
        );

        Schema::create(
            'test_posts', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'title' );
                $table->big_integer( 'user_id' );
                $table->timestamps();
            } 
        );

        // 2. Main Blog Setup (Blog 1)
        // User 1 in Blog 1 (Site-specific)
        $user1 = TestUser::create( ['user_login' => 'blog1_user'] );
        // Network User 1 (Global)
        $wpdb->insert( $wpdb->base_prefix . 'users', ['user_login' => 'global_admin', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' )] );
        $network_user_id = $wpdb->insert_id;

        TestPost::create( ['title' => 'Main Blog Post', 'user_id' => $user1->id] );

        // 3. Switch to Blog 2
        $blog_id = $this->factory->blog->create();
        switch_to_blog( $blog_id );
        
        Schema::create(
            'test_users', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'user_login' );
                $table->timestamps();
            } 
        );
        Schema::create(
            'test_posts', function( Blueprint $table ) {
                $table->big_increments( "id" );
                $table->string( 'title' );
                $table->big_integer( 'user_id' );
                $table->timestamps();
            } 
        );

        // Create Blog 2 Post pointing to GLOBAL Admin
        $post = TestPost::create( ['title' => 'Blog 2 Post', 'user_id' => $network_user_id] );

        // VERIFY: Blog Isolation (test_users should NOT find blog1_user here)
        $this->assertEquals( 0, TestUser::count() );
        $this->assertNull( $post->user ); // This relationship uses TestUser (blog-specific)

        // VERIFY: Network Access (network_user should find the global admin)
        $this->assertNotNull( $post->network_user );
        $this->assertEquals( 'global_admin', $post->network_user->user_login );

        restore_current_blog();
        
        // Clean up
        switch_to_blog( $blog_id );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
        restore_current_blog();
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
        Schema::drop_if_exists( 'users' );
    }
}

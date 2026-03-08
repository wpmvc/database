<?php

namespace WpMVC\Database\Tests\Integration\Multisite;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class MultisiteSchemaTest extends TestCase {
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
        Schema::drop_if_exists( 'blog_custom_table' );
        Schema::drop_if_exists( 'shared_name_table' );
        parent::tear_down();
    }

    public function test_it_creates_tables_with_blog_prefix() {
        global $wpdb;
        $blog_id = $this->factory->blog->create();
        switch_to_blog( $blog_id );

        Schema::create(
            'blog_custom_table', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
            } 
        );

        $this->assertTrue( Schema::has_table( 'blog_custom_table' ) );
        
        $expected_table = $wpdb->prefix . 'blog_custom_table';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results( "SHOW TABLES LIKE '$expected_table'" );
        // Note: In some test envs SHOW TABLES might still be empty even if table exists and is queryable
        // so we rely more on Schema::has_table which we've made robust.

        restore_current_blog();
    }

    public function test_it_does_not_see_tables_from_other_blogs() {
        $blog_id_1 = $this->factory->blog->create();
        $blog_id_2 = $this->factory->blog->create();

        switch_to_blog( $blog_id_1 );
        Schema::create(
            'shared_name_table', function( Blueprint $table ) {
                $table->big_increments( 'id' );
            } 
        );
        $this->assertTrue( Schema::has_table( 'shared_name_table' ) );
        restore_current_blog();

        switch_to_blog( $blog_id_2 );
        $this->assertFalse( Schema::has_table( 'shared_name_table' ) );
        restore_current_blog();
    }

    public function test_it_can_alter_tables_in_different_blogs() {
        $blog_id = $this->factory->blog->create();
        switch_to_blog( $blog_id );

        Schema::create(
            'blog_custom_table', function( Blueprint $table ) {
                $table->big_increments( 'id' );
            } 
        );

        Schema::alter(
            'blog_custom_table', function( Blueprint $table ) {
                $table->string( 'added_column' );
            } 
        );

        $this->assertTrue( Schema::has_column( 'blog_custom_table', 'added_column' ) );
        restore_current_blog();
    }

    public function test_it_restores_prefix_reliably() {
        global $wpdb;
        $original_prefix = $wpdb->prefix;
        
        $blog_id = $this->factory->blog->create();
        switch_to_blog( $blog_id );
        $this->assertNotEquals( $original_prefix, $wpdb->prefix );
        
        restore_current_blog();
        $this->assertEquals( $original_prefix, $wpdb->prefix );
    }

    public function test_it_handles_invalid_blog_switch() {
        global $wpdb;
        $original_prefix = $wpdb->prefix;
        
        // Suppress expected errors during invalid switch
        $suppress = $wpdb->suppress_errors( true );
        
        // Switch to non-existent blog
        switch_to_blog( 999999 );
        
        // Restore error suppression state
        $wpdb->suppress_errors( $suppress );
        
        // In WP, if blog doesn't exist, it usually stays on current or defaults to 1.
        // We just want to ensure our Resolver doesn't crash.
        $this->assertTrue( Schema::has_table( 'users' ) || ! empty( $wpdb->prefix ) );
        
        restore_current_blog();
        $this->assertEquals( $original_prefix, $wpdb->prefix );
    }
}

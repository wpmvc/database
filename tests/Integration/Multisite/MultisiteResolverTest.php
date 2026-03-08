<?php

namespace WpMVC\Database\Tests\Integration\Multisite;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Resolver;

class MultisiteResolverTest extends TestCase {
    public function set_up(): void {
        parent::set_up();
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'Multisite is not enabled.' );
        }

        if ( ! isset( $this->factory ) || ! isset( $this->factory->blog ) ) {
            $this->markTestSkipped( 'Multisite factory not available.' );
        }
    }

    public function test_it_resolves_network_tables_with_base_prefix() {
        global $wpdb;
        $resolver = new Resolver();
        
        // base_prefix is usually 'wp_'
        // prefix might be 'wp_2_' after switching to blog 2
        
        $this->assertEquals( $wpdb->base_prefix . 'users', $resolver->table( 'users' ) );
        $this->assertEquals( $wpdb->base_prefix . 'sitemeta', $resolver->table( 'sitemeta' ) );
    }

    public function test_it_resolves_blog_specific_tables_with_blog_prefix() {
        global $wpdb;
        $resolver = new Resolver();

        $this->assertEquals( $wpdb->prefix . 'posts', $resolver->table( 'posts' ) );
        $this->assertEquals( $wpdb->prefix . 'options', $resolver->table( 'options' ) );
    }

    public function test_it_resolves_different_prefixes_after_switching_blogs() {
        if ( ! function_exists( 'switch_to_blog' ) ) {
            $this->markTestSkipped( 'switch_to_blog function not found.' );
        }

        global $wpdb;
        $resolver = new Resolver();

        $blog_id = $this->factory->blog->create();
        switch_to_blog( $blog_id );

        // After switching, $wpdb->prefix should contain the blog ID (e.g., 'wp_2_')
        // but $wpdb->base_prefix remains 'wp_'
        
        $this->assertEquals( $wpdb->base_prefix . 'users', $resolver->table( 'users' ) );
        $this->assertEquals( $wpdb->prefix . 'posts', $resolver->table( 'posts' ) );
        $this->assertStringContainsString( "_{$blog_id}_", $resolver->table( 'posts' ) );

        restore_current_blog();
    }
}

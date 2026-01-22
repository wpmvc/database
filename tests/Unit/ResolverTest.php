<?php

namespace WpMVC\Database\Tests\Unit;

use WpMVC\Database\Resolver;
use WpMVC\Database\Tests\TestCase;

class ResolverTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that the resolver correctly adds prefixes to standard table names.
     */
    public function it_resolves_standard_table_names() {
        $resolver = new Resolver();
        
        // Default behavior assumes 'wp_' prefix from our bootstrap mock
        $this->assertEquals( 'wp_posts', $resolver->table( 'posts' ) );
    }

    /**
     * @test
     * 
     * Verifies that the resolver correctly handles network-wide table names.
     */
    public function it_resolves_network_table_names() {
        $resolver = new Resolver();
        
        // 'users' is in the default network tables list
        $this->assertEquals( 'wp_users', $resolver->table( 'users' ) );
    }

    /**
     * @test
     * 
     * Verifies that the resolver can resolve multiple table names at once.
     */
    public function it_resolves_multiple_tables() {
        $resolver = new Resolver();
        
        $tables = $resolver->table( 'posts', 'comments' );
        
        $this->assertEquals( ['wp_posts', 'wp_comments'], $tables );
    }

    /**
     * @test
     * 
     * Verifies that custom network tables can be added to the resolver.
     */
    public function it_can_add_custom_network_tables() {
        $resolver = new Resolver();
        
        $resolver->set_network_tables( ['custom_global'] );
        
        $this->assertEquals( 'wp_custom_global', $resolver->table( 'custom_global' ) );
    }
}

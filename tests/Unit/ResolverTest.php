<?php

namespace WpMVC\Database\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Resolver;
use Mockery;

class ResolverTest extends TestCase {
    public function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_resolves_standard_tables() {
        global $wpdb;
        $original_prefix = $wpdb->prefix;
        $wpdb->prefix    = 'wptests_';

        $resolver = new Resolver();
        $this->assertEquals( 'wptests_users', $resolver->table( 'users' ) );

        $wpdb->prefix = $original_prefix;
    }

    public function test_it_resolves_global_tables() {
        global $wpdb;
        $original_base_prefix = $wpdb->base_prefix;
        $wpdb->base_prefix    = 'wp_';

        $resolver = new Resolver();
        // 'site' is in the internal $network_tables list, so it should be resolved automatically
        $this->assertEquals( 'wp_site', $resolver->table( 'site' ) );

        $wpdb->base_prefix = $original_base_prefix;
    }
}

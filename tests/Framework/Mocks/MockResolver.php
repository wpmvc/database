<?php

namespace WpMVC\Database\Tests\Framework\Mocks;

use WpMVC\Database\Resolver;

class MockResolver extends Resolver {
    public function table( $table ) {
        global $wpdb;
        $prefix = isset( $wpdb->prefix ) ? $wpdb->prefix : 'wptests_';
        return $prefix . $table;
    }
}

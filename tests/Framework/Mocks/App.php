<?php

namespace WpMVC;

/**
 * Mock App class for testing database package in isolation.
 */
class App {
    public static $config;

    public static function boot() {
        self::$config = new class {
            public function get( $key, $default = null ) {
                return $default ?: 'wpmvc';
            }
        };
    }
}

// Automatically boot for tests
App::boot();

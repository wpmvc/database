<?php

namespace WpMVC;

/**
 * Mock App class for testing database package in isolation.
 */
class App {
    protected static $config;

    public static function boot() {
        self::$config = new class {
            public function get( $key, $default = null ) {
                return $default ?: 'wpmvc';
            }
        };
    }

    public static function get_config() {
        return self::$config;
    }
}

// Automatically boot for tests
App::boot();

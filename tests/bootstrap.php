<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants if not already defined
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}

if ( ! defined( 'DB_NAME' ) ) {
    define( 'DB_NAME', 'test_db' );
}

// Mock basic WordPress functions if they don't exist
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {}
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action() {}
}

// Mock wpdb class if it doesn't exist (for type hinting and integration testing)
if ( ! class_exists( 'wpdb' ) ) {
    class wpdb {
        public $prefix = 'wp_';

        public $base_prefix = 'wp_';

        public $insert_id = 0;

        public $num_rows = 0;

        public $last_query = '';

        public $queries = [];

        public $mock_results = [];

        public function __construct( $dbuser = '', $dbpassword = '', $dbname = '', $dbhost = '' ) {}

        public function prepare( $query, ...$args ) {
            // For unit tests, return query with placeholders intact
            // Integration tests can override this behavior if needed
            return $query;
        }
        
        public function get_results( $query ) {
            $this->last_query = $query;
            $this->queries[]  = $query;
            
            // Return mock results if available
            if ( ! empty( $this->mock_results ) ) {
                $results        = $this->mock_results;
                $this->num_rows = count( $results );
                return $results;
            }
            
            return [];
        }

        public function get_row( $query ) {
            $results = $this->get_results( $query );
            return $results[0] ?? null;
        }

        public function get_charset_collate() {
            return 'utf8mb4_unicode_ci';
        }
        
        public function query( $query ) {
            $this->last_query = $query;
            $this->queries[]  = $query;
            
            // Simulate INSERT by incrementing insert_id
            if ( stripos( $query, 'INSERT' ) === 0 ) {
                $this->insert_id++;
            }
            
            return true;
        }
    }
}

global $wpdb;
$wpdb = new wpdb();


<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wpmvc_Database
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Mock App for testing
require_once __DIR__ . '/Framework/Mocks/App.php';

// Path to the PHPUnit Polyfills.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo "Could not find $_tests_dir/includes/functions.php\n";
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Loads the library components into the WordPress environment.
 */
function _manually_load_library() {
    // Since this is a library, we don't need to load a plugin file usually.
    // But we might need to define some things or load specific entry points if any.
}

tests_add_filter( 'muplugins_loaded', '_manually_load_library' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

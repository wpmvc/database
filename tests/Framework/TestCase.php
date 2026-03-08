<?php

namespace WpMVC\Database\Tests\Framework;

use WP_UnitTestCase;
use WpMVC\Database\Eloquent\Model;

abstract class TestCase extends WP_UnitTestCase {
    /**
     * Setup before each test.
     */
    public function set_up() {
        parent::set_up();
        Model::unguard();
    }

    /**
     * Teardown after each test.
     */
    public function tear_down() {
        Model::reguard();

        // Clear hooks for test models specifically
        $test_models = ['test_users', 'test_posts', 'test_roles', 'test_profiles', 'test_images', 'test_tags', 'test_audit_models'];
        foreach ( $test_models as $table ) {
            remove_all_filters( "wpmvc_model_saving_{$table}" );
            remove_all_filters( "wpmvc_model_saved_{$table}" );
            remove_all_filters( "wpmvc_model_creating_{$table}" );
            remove_all_filters( "wpmvc_model_created_{$table}" );
            remove_all_filters( "wpmvc_model_updating_{$table}" );
            remove_all_filters( "wpmvc_model_updated_{$table}" );
            remove_all_filters( "wpmvc_model_deleting_{$table}" );
            remove_all_filters( "wpmvc_model_deleted_{$table}" );
        }
        // Clear global hooks
        remove_all_filters( 'wpmvc_model_saving' );
        remove_all_filters( 'wpmvc_model_saved' );
        remove_all_filters( 'wpmvc_model_creating' );
        remove_all_filters( 'wpmvc_model_created' );
        remove_all_filters( 'wpmvc_model_updating' );
        remove_all_filters( 'wpmvc_model_updated' );
        remove_all_filters( 'wpmvc_model_deleting' );
        remove_all_filters( 'wpmvc_model_deleted' );
        
        parent::tear_down();
    }

    /**
     * Assert that a table exists in the database.
     */
    protected function assertTableExists( $table_name ) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        
        $wpdb->suppress_errors();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $res = $wpdb->query( "SELECT 1 FROM `$table` LIMIT 0" );
        $wpdb->suppress_errors( false );

        $this->assertNotFalse( $res, "Table [{$table}] does not exist according to SELECT check." );
    }

    /**
     * Assert that a table does not exist in the database.
     */
    protected function assertTableNotExists( $table_name ) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        
        $wpdb->suppress_errors();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $res = $wpdb->query( "SELECT 1 FROM `$table` LIMIT 0" );
        $wpdb->suppress_errors( false );

        $this->assertFalse( $res, "Table [{$table}] should not exist according to SELECT check." );
    }

    /**
     * Assert that a column exists in a table.
     */
    protected function assertColumnExists( $table_name, $column_name ) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` LIKE %s", $column_name ) );
        $this->assertNotEmpty( $results, "Column [{$column_name}] does not exist in table [{$table}]." );
    }

    /**
     * Assert that an index exists on a table.
     */
    protected function assertHasIndex( $table_name, $index_name ) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", ARRAY_A );
        
        $found = false;
        foreach ( $indexes as $index ) {
            if ( $index['Key_name'] === $index_name ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue( $found, "Index [{$index_name}] does not exist on table [{$table}]." );
    }
}

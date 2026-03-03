<?php
/**
 * Database table name resolver class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database;

defined( "ABSPATH" ) || exit;

use wpdb;

/**
 * Class Resolver
 *
 * Handles resolving table names with proper WordPress prefixes.
 *
 * @package WpMVC\Database
 */
class Resolver {
    protected array $network_tables = [
        'blogmeta',
        'blogs',
        'blog_versions',
        'registration_log',
        'signups',
        'site',
        'sitemeta',
        'usermeta',
        'users'
    ];

    /**
     * Add more tables to the network tables list.
     *
     * @param  string[]  $tables
     * @return void
     */
    public function set_network_tables( array $tables ) {
        $this->network_tables = array_merge( $this->network_tables, $tables );
    }

    /**
     * Resolve one or more table names with prefixes.
     *
     * @param  string  $table  Initial table name if calling with a single argument.
     * @param  string  ...$tables Additional table names.
     * @return string|array
     */
    public function table( string $table ) {
        $table_args = func_get_args();

        if ( 1 === count( $table_args ) ) {
            return $this->resolve_table_name( $table );
        }

        return array_map(
            function( $table ) {
                return $this->resolve_table_name( $table );
            }, $table_args
        );
    }

    /**
     * Resolve a single table name with prefix.
     *
     * @param  string  $table
     * @return string
     */
    protected function resolve_table_name( string $table ) {
        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        if ( in_array( $table, $this->network_tables ) ) {
            return $wpdb->base_prefix . $table;
        }
        return $wpdb->prefix . $table;
    }
}
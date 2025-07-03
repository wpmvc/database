<?php

namespace WpMVC\Database\Schema;

defined( "ABSPATH" ) || exit;

use wpdb;

class Schema {
    /**
     * @param string $table_name
     * @param (Closure(Blueprint): mixed) $callback
     * @param bool $return
     */
    public static function create( string $table_name, callable $callback, bool $return = false ) {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $table_name      = $wpdb->prefix . $table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $blueprint = new Blueprint( $table_name, $charset_collate );
        $callback( $blueprint );
        $sql = $blueprint->to_sql();

        if ( $return ) return $sql;

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
    
        dbDelta( $sql );
        self::apply_foreign_keys( $table_name, $blueprint->get_foreign_keys() );
    }

    public static function drop_if_exists( string $table_name, bool $return = false ) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_name;
        $sql             = "DROP TABLE IF EXISTS `{$full_table_name}`;";
        if ( $return ) return $sql;
        $wpdb->query( $sql );
    }

    public static function rename( string $from, string $to, bool $return = false ) {
        global $wpdb;
        $from_table = $wpdb->prefix . $from;
        $to_table   = $wpdb->prefix . $to;
        $sql        = "RENAME TABLE `{$from_table}` TO `{$to_table}`;";
        if ( $return ) return $sql;
        $wpdb->query( $sql );
    }

    public static function alter( string $table_name, callable $callback, bool $return = false ) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $blueprint = new Blueprint( $full_table_name, $charset_collate );
        $callback( $blueprint );
        $sql = $blueprint->to_alter_sql();

        if ( $return ) return $sql;
        $wpdb->query( $sql );
        self::apply_foreign_keys( $table_name, $blueprint->get_foreign_keys() );
    }

    /**
     * @param string $table_name
     * @param ForeignKey[] $foreign_keys
     * @return void
     */
    protected static function apply_foreign_keys( string $table_name, array $foreign_keys ): void {
        global $wpdb;

        foreach ( $foreign_keys as $fk ) {
            $column     = $fk->get_column();
            $reference  = $fk->get_reference_table();
            $ref_column = $fk->get_reference_column();
            $on_delete  = $fk->get_on_delete();
            $on_update  = $fk->get_on_update();
            $constraint = "fk_{$wpdb->prefix}{$table_name}_{$column}";

            $exists = $wpdb->get_results(
                $wpdb->prepare(
                    "
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND CONSTRAINT_NAME = %s",
                    DB_NAME,
                    $wpdb->prefix . $table_name,
                    $constraint
                )
            );

            if ( empty( $exists ) ) {
                $alter_sql = sprintf(
                    "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES %s(`%s`)%s%s;",
                    $wpdb->prefix . $table_name,
                    $constraint,
                    $column,
                    $reference,
                    $ref_column,
                    $on_delete ? " ON DELETE $on_delete" : "",
                    $on_update ? " ON UPDATE $on_update" : ""
                );
                $wpdb->query( $alter_sql );
            }
        }
    }
}
<?php

namespace WpMVC\Database\Tests\Integration\Schema;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class SchemaTest extends TestCase {
    public function test_it_can_rename_a_table() {
        $from = 'test_rename_from';
        $to   = 'test_rename_to';

        Schema::drop_if_exists( $from );
        Schema::drop_if_exists( $to );

        Schema::create(
            $from, function( Blueprint $table ) {
                $table->big_increments( 'id' );
            } 
        );

        $this->assertTableExists( $from );

        Schema::rename( $from, $to );

        $this->assertTableNotExists( $from );
        $this->assertTableExists( $to );

        Schema::drop_if_exists( $to );
    }

    public function test_it_can_alter_a_table() {
        $table_name = 'test_alter_table';

        Schema::drop_if_exists( $table_name );

        Schema::create(
            $table_name, function( Blueprint $table ) {
                $table->big_increments( 'id' );
            } 
        );

        Schema::alter(
            $table_name, function( Blueprint $table ) {
                $table->string( 'new_column' )->nullable();
                $table->index( 'new_column' );
            } 
        );

        $this->assertColumnExists( $table_name, 'new_column' );
        $this->assertHasIndex( $table_name, 'index_' . md5( 'new_column' ) );

        // Test drop column via alter
        Schema::alter(
            $table_name, function( Blueprint $table ) {
                $table->drop_column( 'new_column' );
            } 
        );

        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $columns = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` LIKE %s", 'new_column' ) );
        $this->assertEmpty( $columns, "Column [new_column] should have been dropped." );

        Schema::drop_if_exists( $table_name );
    }

    public function test_it_can_drop_a_table() {
        $table_name = 'test_drop_table';

        Schema::drop_if_exists( $table_name );

        Schema::create(
            $table_name, function( Blueprint $table ) {
                $table->big_increments( 'id' );
            } 
        );

        $this->assertTableExists( $table_name );

        Schema::drop_if_exists( $table_name );

        $this->assertTableNotExists( $table_name );
    }
}

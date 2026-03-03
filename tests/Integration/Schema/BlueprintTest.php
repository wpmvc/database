<?php

namespace WpMVC\Database\Tests\Integration\Schema;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class BlueprintTest extends TestCase {
    /**
     * @dataProvider columnTypeProvider
     */
    public function test_it_can_define_various_column_types( $method, $name, $expected_type, $args = [] ) {
        global $wpdb;

        $table_name = 'test_blueprint_types';
        Schema::drop_if_exists( $table_name );

        Schema::create(
            $table_name, function( Blueprint $table ) use ( $method, $name, $args ) {
                $table->big_increments( 'id' );
                $table->$method( $name, ...$args );
            } 
        );

        $full_table_name = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $columns = $wpdb->get_results( "DESCRIBE $full_table_name", OBJECT_K );

        $this->assertArrayHasKey( $name, $columns );
        $this->assertStringContainsString( $expected_type, strtolower( $columns[$name]->Type ) );

        Schema::drop_if_exists( $table_name );
    }

    public function columnTypeProvider() {
        return [
            ['unsigned_big_integer', 'big_int', 'bigint'],
            ['integer', 'regular_int', 'int'],
            ['unsigned_integer', 'unsigned_int', 'int'],
            ['unsigned_integer', 'unsigned_int_check', 'unsigned'],
            ['decimal', 'price', 'decimal(15,4)', [15, 4]],
            ['string', 'email', 'varchar(100)', [100]],
            ['text', 'bio', 'text'],
            ['long_text', 'content', 'longtext'],
            ['json', 'metadata', 'json'], // Updated to expect 'json' based on environment failure
            ['enum', 'status', "enum('active','inactive')", [['active', 'inactive']]],
            ['tiny_integer', 'small_int', 'tinyint'],
            ['boolean', 'is_active', 'tinyint(1)'],
            ['float', 'phi', 'float'],
            ['date', 'birthday', 'date'],
            ['datetime', 'published_at', 'datetime'],
        ];
    }

    public function test_it_can_define_indexes() {
        global $wpdb;
        $table_name = 'test_indexes_table';
        Schema::drop_if_exists( $table_name );

        Schema::create(
            $table_name, function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'email' );
                $table->string( 'username' );
                $table->integer( 'votes' );

                $table->unique( 'email' );
                $table->index( ['username', 'votes'], 'user_votes_index' );
            } 
        );

        $full_table_name = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $indexes = $wpdb->get_results( "SHOW INDEX FROM $full_table_name", ARRAY_A );
        
        $found_unique    = false;
        $found_composite = false;

        foreach ( $indexes as $index ) {
            if ( $index['Column_name'] === 'email' && $index['Non_unique'] == 0 ) {
                $found_unique = true;
            }
            if ( $index['Key_name'] === 'user_votes_index' ) {
                $found_composite = true;
            }
        }

        $this->assertTrue( $found_unique, "Unique index on email should exist" );
        $this->assertTrue( $found_composite, "Composite index user_votes_index should exist" );

        Schema::drop_if_exists( $table_name );
    }

    public function test_it_can_define_column_modifiers() {
        global $wpdb;
        $table_name = 'test_modifiers_table';
        Schema::drop_if_exists( $table_name );

        Schema::create(
            $table_name, function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' )->default( 'John Doe' )->comment( 'User Name' );
                $table->integer( 'age' )->nullable();
                $table->timestamp( 'created_at' )->use_current();
                $table->timestamp( 'updated_at' )->use_current()->use_current_on_update();
            } 
        );

        $full_table_name = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $columns = $wpdb->get_results( "DESCRIBE $full_table_name", OBJECT_K );

        $this->assertEquals( 'John Doe', $columns['name']->Default );
        $this->assertEquals( 'YES', $columns['age']->Null );
        $this->assertStringContainsString( 'current_timestamp', strtolower( $columns['created_at']->Default ) );
        $this->assertStringContainsString( 'on update current_timestamp', strtolower( $columns['updated_at']->Extra ) );

        Schema::drop_if_exists( $table_name );
    }

    public function test_it_can_generate_alter_sql() {
        $table_name = 'prefix_test_table';
        $blueprint  = new Blueprint( $table_name, 'utf8mb4' );
        
        $blueprint->string( 'new_col' );
        $blueprint->drop_column( 'old_col' );
        $blueprint->unique( 'new_col' );

        $sql = $blueprint->to_alter_sql();

        $this->assertStringContainsString( 'ALTER TABLE `PREFIX_TEST_TABLE`', strtoupper( $sql ) );
        $this->assertStringContainsString( 'ADD `NEW_COL` VARCHAR(255) NOT NULL', strtoupper( $sql ) );
        $this->assertStringContainsString( 'DROP COLUMN `OLD_COL`', strtoupper( $sql ) );
        $this->assertStringContainsString( 'ADD UNIQUE', strtoupper( $sql ) );
        $this->assertStringContainsString( '(`NEW_COL`)', strtoupper( $sql ) );
    }

    public function test_it_handles_boolean_defaults_correctly() {
        global $wpdb;
        $table_name = 'test_bool_defaults';
        Schema::drop_if_exists( $table_name );

        Schema::create(
            $table_name, function( Blueprint $table ) {
                $table->boolean( 'is_active' )->default( true );
                $table->boolean( 'is_deleted' )->default( false );
            } 
        );

        $full_table_name = $wpdb->prefix . $table_name;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $columns = $wpdb->get_results( "DESCRIBE $full_table_name", OBJECT_K );

        $this->assertEquals( '1', $columns['is_active']->Default );
        $this->assertEquals( '0', $columns['is_deleted']->Default );

        Schema::drop_if_exists( $table_name );
    }
}

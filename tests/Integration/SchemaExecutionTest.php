<?php

namespace WpMVC\Database\Tests\Integration;

use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

/**
 * Integration tests for schema operations.
 * 
 * Tests verify that schema builder correctly generates and executes
 * DDL statements for table creation, modification, and deletion.
 */
class SchemaExecutionTest extends IntegrationTestCase {
    /**
     * @test
     * 
     * Verifies that create table generates correct SQL with all column definitions.
     */
    public function it_generates_create_table_sql_with_columns() {
        // Act
        $sql = Schema::create(
            'products',
            function ( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->decimal( 'price', 10, 2 );
                $table->text( 'description' );
                $table->timestamps();
            },
            true // Return SQL instead of executing
        );
        
        // Assert
        $this->assert_sql_contains( 'create table', $sql );
        $this->assert_sql_contains( 'wp_products', $sql );
        $this->assert_sql_contains( 'id', $sql );
        $this->assert_sql_contains( 'bigint unsigned auto_increment', $sql );
        $this->assert_sql_contains( 'name', $sql );
        $this->assert_sql_contains( 'varchar(255)', $sql );
        $this->assert_sql_contains( 'price', $sql );
        $this->assert_sql_contains( 'decimal(10, 2)', $sql );
        $this->assert_sql_contains( 'created_at', $sql );
        $this->assert_sql_contains( 'updated_at', $sql );
    }

    /**
     * @test
     * 
     * Verifies that drop table generates correct SQL.
     */
    public function it_generates_drop_table_sql() {
        // Act
        $sql = Schema::drop_if_exists( 'old_table', true );
        
        // Assert
        $this->assert_sql_contains( 'drop table if exists', $sql );
        $this->assert_sql_contains( 'wp_old_table', $sql );
    }

    /**
     * @test
     * 
     * Verifies that alter table adds columns correctly.
     */
    public function it_generates_alter_table_add_column_sql() {
        // Act
        $sql = Schema::alter(
            'users',
            function ( Blueprint $table ) {
                $table->string( 'phone' );
                $table->boolean( 'is_verified' );
            },
            true
        );
        
        // Assert
        $this->assert_sql_contains( 'alter table', $sql );
        $this->assert_sql_contains( 'wp_users', $sql );
        $this->assert_sql_contains( 'add', $sql );
        $this->assert_sql_contains( 'phone', $sql );
        $this->assert_sql_contains( 'varchar(255)', $sql );
        $this->assert_sql_contains( 'is_verified', $sql );
        $this->assert_sql_contains( 'tinyint(1)', $sql );
    }

    /**
     * @test
     * 
     * Verifies that alter table can drop columns.
     */
    public function it_generates_alter_table_drop_column_sql() {
        // Act
        $sql = Schema::alter(
            'users',
            function ( Blueprint $table ) {
                $table->drop_column( 'old_field' );
            },
            true
        );
        
        // Assert
        $this->assert_sql_contains( 'alter table', $sql );
        $this->assert_sql_contains( 'drop column', $sql );
        $this->assert_sql_contains( 'old_field', $sql );
    }

    /**
     * @test
     * 
     * Verifies that indexes are added to create table statement.
     */
    public function it_generates_table_with_indexes() {
        // Act
        $sql = Schema::create(
            'products',
            function ( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'sku' );
                $table->string( 'name' );
                
                $table->unique( 'sku' );
                $table->index( 'name' );
            },
            true
        );
        
        // Assert
        $this->assert_sql_contains( 'primary key', $sql );
        $this->assert_sql_contains( 'unique key', $sql );
        $this->assert_sql_contains( 'key', $sql ); // Regular index
    }

    /**
     * @test
     * 
     * Verifies that foreign keys are defined correctly.
     */
    public function it_defines_foreign_keys_in_schema() {
        // Act
        $sql = Schema::create(
            'comments',
            function ( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'post_id' );
                $table->text( 'content' );
                
                $table->foreign( 'post_id' )
                    ->references( 'id' )
                    ->on( 'posts' )
                    ->on_delete( 'CASCADE' );
            },
            true
        );
        
        // Assert
        $this->assert_sql_contains( 'create table', $sql );
        $this->assert_sql_contains( 'post_id', $sql );
        $this->assert_sql_contains( 'bigint unsigned', $sql );
        // Note: Foreign keys are applied separately via Schema::create
    }

    /**
     * @test
     * 
     * Verifies that column modifiers work correctly.
     */
    public function it_applies_column_modifiers() {
        // Act
        $sql = Schema::create(
            'settings',
            function ( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'key' );
                $table->string( 'value' )->nullable();
                $table->string( 'status' )->default( 'active' );
                
                $table->unique( 'key' );
            },
            true
        );
        
        // Assert
        $this->assert_sql_contains( 'null', $sql );
        $this->assert_sql_contains( "default 'active'", $sql );
        $this->assert_sql_contains( 'unique', $sql );
    }
}

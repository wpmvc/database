<?php

namespace WpMVC\Database\Tests\Unit\Schema;

use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\TestCase;

class BlueprintTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that the blueprint generates the correct SQL for creating a table.
     */
    public function it_generates_create_table_sql() {
        $blueprint = new Blueprint( 'wp_products', 'utf8mb4_unicode_ci' );
        
        $blueprint->big_increments( 'id' );
        $blueprint->string( 'title' );
        
        $sql = $blueprint->to_sql();
        
        $expected = "CREATE TABLE `wp_products` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) utf8mb4_unicode_ci;";

        $this->assertEquals( $expected, $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint correctly handles nullable columns and default values.
     */
    public function it_handles_nullable_and_default() {
        $blueprint = new Blueprint( 'wp_posts', 'utf8mb4_unicode_ci' );
        
        $blueprint->string( 'description' )->nullable();
        $blueprint->string( 'status' )->default( 'draft' );
        
        $sql = $blueprint->to_sql();
        
        $this->assertStringContainsString( "`description` VARCHAR(255) NULL", $sql );
        $this->assertStringContainsString( "`status` VARCHAR(255) NOT NULL DEFAULT 'draft'", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint correctly adds timestamps columns.
     */
    public function it_handles_timestamps() {
        $blueprint = new Blueprint( 'wp_posts', 'utf8mb4_unicode_ci' );
        
        $blueprint->timestamps();
        
        $sql = $blueprint->to_sql();
        
        $this->assertStringContainsString( "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP", $sql );
        $this->assertStringContainsString( "`updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates the correct SQL for altering a table.
     */
    public function it_generates_alter_table_sql() {
        $blueprint = new Blueprint( 'wp_users', 'utf8mb4_unicode_ci' );
        
        $blueprint->string( 'phone' )->after( 'email' );
        $blueprint->drop_column( 'old_column' );
        
        $sql = $blueprint->to_alter_sql();
        
        $expected = "ALTER TABLE `wp_users`
    ADD `phone` VARCHAR(255) NOT NULL AFTER `email`,
    DROP COLUMN `old_column`;";

        $this->assertEquals( $expected, $sql );
    }
}

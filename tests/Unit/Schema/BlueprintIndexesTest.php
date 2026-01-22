<?php

namespace WpMVC\Database\Tests\Unit\Schema;

use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\TestCase;

class BlueprintIndexesTest extends TestCase {
    protected $blueprint;

    protected function setUp(): void {
        parent::setUp();
        $this->blueprint = new Blueprint( 'wp_table', 'utf8mb4_unicode_ci' );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for a single-column primary key.
     */
    public function it_creates_primary_key_on_single_column() {
        $this->blueprint->primary( 'uuid' );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "PRIMARY KEY (`uuid`)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for a composite primary key.
     */
    public function it_creates_primary_key_on_multiple_columns() {
        $this->blueprint->primary( ['user_id', 'role_id'] );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "PRIMARY KEY (`user_id`, `role_id`)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for a unique index.
     */
    public function it_creates_unique_index() {
        $this->blueprint->unique( 'email' );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "UNIQUE KEY `unique_", $sql );
        $this->assertStringContainsString( "(`email`)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for a unique index with a custom name.
     */
    public function it_creates_unique_index_with_custom_name() {
        $this->blueprint->unique( 'email', 'unique_email_idx' );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "UNIQUE KEY `unique_email_idx` (`email`)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for a regular index.
     */
    public function it_creates_regular_index() {
        $this->blueprint->index( 'status' );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "KEY `index_", $sql );
        $this->assertStringContainsString( "(`status`)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for a regular index with a custom name.
     */
    public function it_creates_regular_index_with_custom_name() {
        $this->blueprint->index( ['status', 'created_at'], 'idx_status_date' );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "KEY `idx_status_date` (`status`, `created_at`)", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for dropping an index in an ALTER TABLE statement.
     */
    public function it_drops_index_in_alter() {
        $this->blueprint->drop_index( 'idx_temp' );
        
        $sql = $this->blueprint->to_alter_sql();
        
        $this->assertStringContainsString( "DROP INDEX `idx_temp`", $sql );
    }
}

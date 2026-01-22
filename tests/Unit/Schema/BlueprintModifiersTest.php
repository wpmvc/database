<?php

namespace WpMVC\Database\Tests\Unit\Schema;

use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\TestCase;

class BlueprintModifiersTest extends TestCase {
    protected $blueprint;

    protected function setUp(): void {
        parent::setUp();
        $this->blueprint = new Blueprint( 'wp_table', 'utf8mb4_unicode_ci' );
    }

    /**
     * @test
     * 
     * Verifies that a comment can be added to a column definition.
     */
    public function it_adds_comment_to_column() {
        $this->blueprint->string( 'code' )->comment( 'Unique product code' );
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "COMMENT 'Unique product code'", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the `use_current` modifier adds DEFAULT CURRENT_TIMESTAMP.
     */
    public function it_sets_use_current_for_timestamp() {
        $this->blueprint->timestamp( 'created_at' )->use_current();
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "DEFAULT CURRENT_TIMESTAMP", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the `use_current_on_update` modifier adds ON UPDATE CURRENT_TIMESTAMP.
     */
    public function it_sets_use_current_on_update_for_timestamp() {
        $this->blueprint->timestamp( 'updated_at' )->use_current_on_update();
        
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "ON UPDATE CURRENT_TIMESTAMP", $sql );
    }

    /**
     * @test
     * 
     * Verifies that multiple modifiers can be chained on a column definition.
     */
    public function it_chains_multiple_modifiers() {
        $this->blueprint->string( 'status' )
            ->nullable()
            ->default( 'pending' )
            ->comment( 'Status' );
            
        $sql = $this->blueprint->to_sql();
        
        $this->assertStringContainsString( "NULL DEFAULT 'pending' COMMENT 'Status'", $sql );
    }
}

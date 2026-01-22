<?php

namespace WpMVC\Database\Tests\Unit\Schema;

use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\TestCase;

class BlueprintColumnsTest extends TestCase {
    protected $blueprint;

    protected function setUp(): void {
        parent::setUp();
        $this->blueprint = new Blueprint( 'wp_table', 'utf8mb4_unicode_ci' );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for various numeric column types.
     */
    public function it_creates_various_numeric_columns() {
        $this->blueprint->unsigned_big_integer( 'user_id' );
        $this->blueprint->integer( 'count' );
        $this->blueprint->unsigned_integer( 'visits' );
        $this->blueprint->tiny_integer( 'is_active' );
        $this->blueprint->decimal( 'price', 8, 2 );

        $sql = $this->blueprint->to_sql();

        $this->assertStringContainsString( "`user_id` BIGINT UNSIGNED NOT NULL", $sql );
        $this->assertStringContainsString( "`count` INT NOT NULL", $sql );
        $this->assertStringContainsString( "`visits` INT UNSIGNED NOT NULL", $sql );
        $this->assertStringContainsString( "`is_active` TINYINT NOT NULL", $sql );
        $this->assertStringContainsString( "`price` DECIMAL(8, 2) NOT NULL", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for text and string column types.
     */
    public function it_creates_text_columns() {
        $this->blueprint->string( 'name', 100 );
        $this->blueprint->text( 'bio' );
        $this->blueprint->long_text( 'content' );

        $sql = $this->blueprint->to_sql();

        $this->assertStringContainsString( "`name` VARCHAR(100) NOT NULL", $sql );
        $this->assertStringContainsString( "`bio` TEXT NOT NULL", $sql );
        $this->assertStringContainsString( "`content` LONGTEXT", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for date and time column types.
     */
    public function it_creates_date_time_columns() {
        $this->blueprint->timestamp( 'published_at' );

        $sql = $this->blueprint->to_sql();

        $this->assertStringContainsString( "`published_at` TIMESTAMP", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for JSON columns.
     */
    public function it_creates_json_column() {
        $this->blueprint->json( 'metadata' );

        $sql = $this->blueprint->to_sql();

        $this->assertStringContainsString( "`metadata` JSON NOT NULL", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for ENUM columns.
     */
    public function it_creates_enum_column() {
        $this->blueprint->enum( 'status', ['draft', 'publish'] );

        $sql = $this->blueprint->to_sql();

        $this->assertStringContainsString( "`status` ENUM('draft','publish') NOT NULL", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the blueprint generates correct SQL for boolean columns.
     */
    public function it_creates_boolean_column() {
        $this->blueprint->boolean( 'is_visible' );

        $sql = $this->blueprint->to_sql();

        $this->assertStringContainsString( "`is_visible` TINYINT(1) NOT NULL", $sql );
    }
}

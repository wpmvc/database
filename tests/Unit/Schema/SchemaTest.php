<?php

namespace WpMVC\Database\Tests\Unit\Schema;

use Mockery;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\TestCase;

class SchemaTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that the Schema facade generates correct SQL for creating a table.
     */
    public function it_generates_create_table_sql() {
        // We use return=true to get SQL back instead of executing it
        $sql = Schema::create(
            'users', function ( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'email' );
            }, true 
        );

        $this->assertStringContainsString( "CREATE TABLE `wp_users`", $sql );
        $this->assertStringContainsString( "`id` BIGINT UNSIGNED AUTO_INCREMENT", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the Schema facade generates correct SQL for dropping a table if it exists.
     */
    public function it_generates_drop_if_exists_sql() {
        $sql = Schema::drop_if_exists( 'users', true );
        
        $this->assertEquals( "DROP TABLE IF EXISTS `wp_users`;", $sql );
    }

    /**
     * @test
     * 
     * Verifies that the Schema facade generates correct SQL for altering a table.
     */
    public function it_generates_alter_table_sql() {
        $sql = Schema::alter(
            'users', function ( Blueprint $table ) {
                $table->string( 'name' );
            }, true 
        );
        
        $this->assertStringContainsString( "ALTER TABLE `wp_users`", $sql );
        $this->assertStringContainsString( "ADD `name` VARCHAR(255)", $sql );
    }
}

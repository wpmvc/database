<?php

namespace WpMVC\Database\Tests\Unit\Schema;

use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\TestCase;
use WpMVC\Database\Schema\ForeignKey;

class ForeignKeyTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that a foreign key reference can be correctly created.
     */
    public function it_creates_foreign_key_reference() {
        $blueprint = new Blueprint( 'wp_posts', 'utf8' );
        
        $fk = $blueprint->foreign( 'user_id' )
            ->references( 'id' )
            ->on( 'users' );
            
        $this->assertInstanceOf( ForeignKey::class, $fk );
        $this->assertEquals( 'user_id', $fk->get_column() );
        $this->assertEquals( 'wp_users', $fk->get_reference_table() );
        $this->assertEquals( 'id', $fk->get_reference_column() );
    }

    /**
     * @test
     * 
     * Verifies that the ON DELETE action can be set to CASCADE.
     */
    public function it_sets_on_delete_cascade() {
        $fk = new ForeignKey( 'user_id' );
        $fk->on_delete( 'CASCADE' );
        
        $this->assertEquals( 'CASCADE', $fk->get_on_delete() );
    }

    /**
     * @test
     * 
     * Verifies that the ON DELETE action can be set to SET NULL.
     */
    public function it_sets_on_delete_set_null() {
        $fk = new ForeignKey( 'user_id' );
        $fk->on_delete( 'SET NULL' );
        
        $this->assertEquals( 'SET NULL', $fk->get_on_delete() );
    }

    /**
     * @test
     * 
     * Verifies that the ON UPDATE action can be set to CASCADE.
     */
    public function it_sets_on_update_cascade() {
        $fk = new ForeignKey( 'user_id' );
        $fk->on_update( 'CASCADE' );
        
        $this->assertEquals( 'CASCADE', $fk->get_on_update() );
    }
}

<?php

namespace WpMVC\Database\Tests\Integration\Schema;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Schema\ForeignKey;

class ForeignKeyTest extends TestCase {
    public function test_it_can_define_foreign_keys_fluently() {
        $table_name = 'test_fk_table';
        
        $sql = Schema::create(
            $table_name, function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'user_id' );
            
                // Test fluent API
                $fk = $table->foreign( 'user_id' )
                ->references( 'id' )
                ->on( 'wp_users' )
                ->on_delete( 'cascade' )
                ->on_update( 'set null' );
                
                $this->assertInstanceOf( ForeignKey::class, $fk );
            }, true 
        ); // return SQL
        
        $this->assertStringContainsString( 'CREATE TABLE', $sql );
        $this->assertStringContainsString( 'USER_ID` BIGINT UNSIGNED', strtoupper( $sql ) );
    }

    public function test_schema_identifies_foreign_keys() {
        $captured_fks = [];
        
        Schema::create(
            'test_fk_capture', function( Blueprint $table ) use ( &$captured_fks ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'author_id' );
                $table->foreign( 'author_id' )->references( 'id' )->on( 'authors' );
            
                $captured_fks = $table->get_foreign_keys();
            }, true 
        );

        $this->assertCount( 1, $captured_fks );
        $this->assertEquals( 'author_id', $captured_fks[0]->get_column() );
        $this->assertEquals( 'id', $captured_fks[0]->get_reference_column() );
        
        $resolver = new \WpMVC\Database\Resolver();
        $this->assertEquals( $resolver->table( 'authors' ), $captured_fks[0]->get_reference_table() );
    }

    public function test_it_captures_on_delete_and_on_update() {
        $captured_fks = [];
        
        Schema::create(
            'test_fk_actions', function( Blueprint $table ) use ( &$captured_fks ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'user_id' );
                $table->foreign( 'user_id' )
                ->references( 'id' )
                ->on( 'users' )
                ->on_delete( 'cascade' )
                ->on_update( 'restrict' );
            
                $captured_fks = $table->get_foreign_keys();
            }, true 
        );

        $this->assertEquals( 'CASCADE', $captured_fks[0]->get_on_delete() );
        $this->assertEquals( 'RESTRICT', $captured_fks[0]->get_on_update() );
    }
}

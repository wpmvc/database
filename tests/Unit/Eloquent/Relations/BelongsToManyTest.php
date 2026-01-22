<?php

namespace WpMVC\Database\Tests\Unit\Eloquent\Relations;

use WpMVC\Database\Eloquent\Relations\BelongsToMany;
use WpMVC\Database\Tests\Fixtures\Models\Role;
use WpMVC\Database\Tests\Fixtures\Models\RoleUserPivot;
use WpMVC\Database\Tests\TestCase;

/**
 * Tests for BelongsToMany (many-to-many) relationship.
 */
class BelongsToManyTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that BelongsToMany relationship correctly stores pivot table,
     * foreign/local pivot keys, and foreign/local keys for many-to-many relationships.
     */
    public function it_creates_belongs_to_many_relationship() {
        $relation = new BelongsToMany( 
            Role::class, 
            RoleUserPivot::class, 
            'user_id', 
            'role_id', 
            'id', 
            'id' 
        );
        
        $this->assertInstanceOf( BelongsToMany::class, $relation );
        $this->assertInstanceOf( RoleUserPivot::class, $relation->pivot );
        $this->assertEquals( 'user_id', $relation->foreign_pivot_key );
        $this->assertEquals( 'role_id', $relation->local_pivot_key );
        $this->assertEquals( 'id', $relation->foreign_key );
        $this->assertEquals( 'id', $relation->local_key );
        $this->assertInstanceOf( Role::class, $relation->get_related() );
    }
}

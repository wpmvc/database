<?php

namespace WpMVC\Database\Tests\Unit\Eloquent\Relations;

use WpMVC\Database\Eloquent\Relations\HasOne;
use WpMVC\Database\Tests\Fixtures\Models\User;
use WpMVC\Database\Tests\Fixtures\Models\Profile;
use WpMVC\Database\Tests\TestCase;

/**
 * Tests for HasOne relationship.
 */
class HasOneTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that HasOne relationship correctly stores foreign and local keys.
     */
    public function it_creates_has_one_relationship() {
        $relation = new HasOne( Profile::class, 'user_id', 'id' );
        
        $this->assertInstanceOf( HasOne::class, $relation );
        $this->assertEquals( 'user_id', $relation->foreign_key );
        $this->assertEquals( 'id', $relation->local_key );
        $this->assertInstanceOf( Profile::class, $relation->get_related() );
    }
}

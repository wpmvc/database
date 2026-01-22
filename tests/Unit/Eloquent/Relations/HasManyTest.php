<?php

namespace WpMVC\Database\Tests\Unit\Eloquent\Relations;

use WpMVC\Database\Eloquent\Relations\HasMany;
use WpMVC\Database\Tests\Fixtures\Models\Post;
use WpMVC\Database\Tests\Fixtures\Models\Comment;
use WpMVC\Database\Tests\TestCase;

/**
 * Tests for HasMany relationship.
 */
class HasManyTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that HasMany relationship correctly stores foreign and local keys.
     */
    public function it_creates_has_many_relationship() {
        $relation = new HasMany( Comment::class, 'post_id', 'id' );
        
        $this->assertInstanceOf( HasMany::class, $relation );
        $this->assertEquals( 'post_id', $relation->foreign_key );
        $this->assertEquals( 'id', $relation->local_key );
        $this->assertInstanceOf( Comment::class, $relation->get_related() );
    }
}

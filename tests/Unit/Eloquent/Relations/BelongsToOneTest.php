<?php

namespace WpMVC\Database\Tests\Unit\Eloquent\Relations;

use WpMVC\Database\Eloquent\Relations\BelongsToOne;
use WpMVC\Database\Tests\Fixtures\Models\Comment;
use WpMVC\Database\Tests\Fixtures\Models\Post;
use WpMVC\Database\Tests\TestCase;

/**
 * Tests for BelongsToOne relationship (inverse of HasOne/HasMany).
 */
class BelongsToOneTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that BelongsToOne relationship correctly stores foreign and local keys.
     */
    public function it_creates_belongs_to_one_relationship() {
        $relation = new BelongsToOne( Post::class, 'post_id', 'id' );
        
        $this->assertInstanceOf( BelongsToOne::class, $relation );
        $this->assertEquals( 'post_id', $relation->foreign_key );
        $this->assertEquals( 'id', $relation->local_key );
        $this->assertInstanceOf( Post::class, $relation->get_related() );
    }
}

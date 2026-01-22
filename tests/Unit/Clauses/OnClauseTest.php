<?php

namespace WpMVC\Database\Tests\Unit\Clauses;

use WpMVC\Database\Clauses\OnClause;
use WpMVC\Database\Tests\Fixtures\Helpers\ClauseTestHelper;
use WpMVC\Database\Tests\TestCase;

/**
 * Helper class for testing OnClause trait.
 * Extends ClauseTestHelper to inherit common clause testing methods.
 */
class OnClauseUser extends ClauseTestHelper {
    use OnClause;
}

/**
 * Tests for OnClause trait functionality (used in JOIN clauses).
 */
class OnClauseTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that on() method correctly adds a basic ON clause
     * with AND boolean operator for JOIN conditions.
     */
    public function it_can_add_on_clause() {
        $user = new OnClauseUser();
        
        $user->on( 'first', '=', 'second' );
        
        $ons = $user->get_ons();
        $this->assertCount( 1, $ons );
        $this->assertEquals( 'and', $ons[0]['boolean'] );
        $this->assertEquals( 'basic', $ons[0]['type'] );
    }

    /**
     * @test
     * 
     * Verifies that or_on() method correctly adds an ON clause
     * with OR boolean operator for JOIN conditions.
     */
    public function it_can_add_or_on_clause() {
        $user = new OnClauseUser();
        
        $user->or_on( 'a', '=', 'b' );
        
        $ons = $user->get_ons();
        $this->assertEquals( 'or', $ons[0]['boolean'] );
    }
}

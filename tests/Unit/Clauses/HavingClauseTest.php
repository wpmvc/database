<?php

namespace WpMVC\Database\Tests\Unit\Clauses;

use WpMVC\Database\Clauses\HavingClause;
use WpMVC\Database\Tests\Fixtures\Helpers\ClauseTestHelper;
use WpMVC\Database\Tests\TestCase;

/**
 * Helper class for testing HavingClause trait.
 * Extends ClauseTestHelper to inherit common clause testing methods.
 */
class HavingClauseUser extends ClauseTestHelper {
    use HavingClause;
}

/**
 * Tests for HavingClause trait functionality.
 */
class HavingClauseTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that having() method correctly adds a basic HAVING clause
     * with AND boolean operator.
     */
    public function it_can_add_having_clause() {
        $user = new HavingClauseUser();
        
        $user->having( 'count', '>', 5 );
        
        $havings = $user->get_havings();
        $this->assertCount( 1, $havings );
        $this->assertEquals( 'and', $havings[0]['boolean'] );
        $this->assertEquals( 'basic', $havings[0]['type'] );
        $this->assertEquals( 'count', $havings[0]['column'] );
    }

    /**
     * @test
     * 
     * Verifies that or_having() method correctly adds a HAVING clause
     * with OR boolean operator.
     */
    public function it_can_add_or_having_clause() {
        $user = new HavingClauseUser();
        
        $user->or_having( 'total', '=', 100 );
        
        $havings = $user->get_havings();
        $this->assertEquals( 'or', $havings[0]['boolean'] );
    }

    /**
     * @test
     * 
     * Verifies that having_raw() method correctly adds a raw HAVING clause
     * with custom SQL and bindings.
     */
    public function it_can_add_having_raw_clause() {
        $user = new HavingClauseUser();
        
        $user->having_raw( 'SUM(total) > ?', [1000] );
        
        $havings = $user->get_havings();
        $this->assertEquals( 'raw', $havings[0]['type'] );
        $this->assertEquals( 'SUM(total) > ?', $havings[0]['sql'] );
    }
}

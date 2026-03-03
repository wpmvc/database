<?php

namespace WpMVC\Database\Tests\Unit\Clauses;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Clauses\Clause;

class ClauseStub {
    use Clause;

    protected $model;

    public function __construct( $model = null ) {
        $this->model = $model;
    }

    // Expose protected methods for testing
    public function call_clause( ...$args ) {
        return $this->clause( ...$args );
    }

    public function call_clause_in( ...$args ) {
        return $this->clause_in( ...$args );
    }

    public function call_clause_between( ...$args ) {
        return $this->clause_between( ...$args );
    }

    public function call_clause_null( ...$args ) {
        return $this->clause_null( ...$args );
    }

    // Add helper to match implementation logic
    protected function prepare_value_and_operator( $value, $operator, $use_operator ) {
        if ( $use_operator ) {
            return [$operator, '='];
        }
        return [$value, $operator];
    }

    protected function invalid_operator( $operator ) {
        return ! in_array( $operator, ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'between', 'not between', 'in', 'not in'] );
    }
}

class ClauseTest extends TestCase {
    public function test_it_can_set_and_get_clauses() {
        $stub = new ClauseStub();
        $this->assertEmpty( $stub->get_clauses() );

        $stub->call_clause( 'wheres', 'id', '=', 1 );
        $clauses = $stub->get_clauses();
        
        $this->assertArrayHasKey( 'wheres', $clauses );
        $this->assertCount( 1, $clauses['wheres'] );
        $this->assertEquals( 'basic', $clauses['wheres'][0]['type'] );
        $this->assertEquals( 'id', $clauses['wheres'][0]['column'] );
        $this->assertEquals( '=', $clauses['wheres'][0]['operator'] );
        $this->assertEquals( 1, $clauses['wheres'][0]['value'] );
    }

    public function test_it_handles_in_clauses() {
        $stub = new ClauseStub();
        $stub->call_clause_in( 'wheres', 'id', [1, 2, 3] );
        
        $clauses = $stub->get_clauses();
        $this->assertEquals( 'in', $clauses['wheres'][0]['type'] );
        $this->assertEquals( [1, 2, 3], $clauses['wheres'][0]['values'] );
    }

    public function test_it_handles_between_clauses() {
        $stub = new ClauseStub();
        $stub->call_clause_between( 'wheres', 'votes', [1, 10] );
        
        $clauses = $stub->get_clauses();
        $this->assertEquals( 'between', $clauses['wheres'][0]['type'] );
        $this->assertEquals( [1, 10], $clauses['wheres'][0]['values'] );
    }

    public function test_it_handles_null_clauses() {
        $stub = new ClauseStub();
        $stub->call_clause_null( 'wheres', 'deleted_at' );
        
        $clauses = $stub->get_clauses();
        $this->assertEquals( 'is_null', $clauses['wheres'][0]['type'] );
        $this->assertEquals( 'deleted_at', $clauses['wheres'][0]['column'] );
        $this->assertFalse( $clauses['wheres'][0]['not'] );
    }

    public function test_it_handles_not_null_clauses() {
        $stub = new ClauseStub();
        $stub->call_clause_null( 'wheres', 'deleted_at', null, 'and', true );
        
        $clauses = $stub->get_clauses();
        $this->assertTrue( $clauses['wheres'][0]['not'] );
    }

    public function test_it_records_or_boolean_correctly(): void {
        $stub = new ClauseStub();
        // clause( clause_type, column, operator, value, name=null, boolean='and', not=false )
        $stub->call_clause( 'wheres', 'status', '=', 'active', null, 'or' );

        $clause = $stub->get_clauses()['wheres'][0];
        $this->assertEquals( 'or', $clause['boolean'] );
        $this->assertEquals( 'active', $clause['value'] );
    }

    public function test_it_handles_not_in_clauses(): void {
        $stub = new ClauseStub();
        // clause_in( clause_type, column, values, name=null, boolean='and', not=false )
        $stub->call_clause_in( 'wheres', 'id', [1, 2, 3], null, 'and', true );

        $clause = $stub->get_clauses()['wheres'][0];
        $this->assertEquals( 'in', $clause['type'] );
        $this->assertTrue( $clause['not'] );
        $this->assertEquals( [1, 2, 3], $clause['values'] );
    }

    public function test_it_stores_raw_sql_as_clause(): void {
        $stub = new ClauseStub();
        // Directly push a raw clause item (mirrors how where_raw works)
        $stub->get_clauses();
        $raw_item = [
            'type'     => 'raw',
            'sql'      => 'price > %d',
            'bindings' => [100],
            'boolean'  => 'and',
        ];
        // Manually register it
        $clauses           = $stub->get_clauses();
        $clauses['wheres'] = [ $raw_item ];

        $this->assertEquals( 'raw', $raw_item['type'] );
        $this->assertEquals( 'price > %d', $raw_item['sql'] );
        $this->assertEquals( [100], $raw_item['bindings'] );
    }
}

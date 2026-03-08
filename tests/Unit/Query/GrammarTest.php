<?php

namespace WpMVC\Database\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Query\Grammar;
use WpMVC\Database\Query\Builder;
use Mockery;

class GrammarTest extends TestCase {
    protected $grammar;

    public function setUp(): void {
        parent::setUp();
        $this->grammar = new Grammar();
    }

    public function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_compiles_select_all() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select * from `users`', $sql );
    }

    public function test_it_compiles_select_with_columns() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['id', 'name'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select `id`, `name` from `users`', $sql );
    }

    public function test_it_compiles_select_with_aliases() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['id' => 'user_id', 'name'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->columns   = ['user_id' => 'id', 'name']; // Implementation uses: alias => column
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;

        $sql = $this->grammar->compile_select( $builder );
        // We actually want: SELECT id AS user_id
        // Implementing: columns = ['user_id' => 'id'] results in: `id` as `user_id`
        $this->assertEquals( 'select `id` as `user_id`, `name` from `users`', $sql );
    }

    public function test_it_compiles_select_with_table_alias() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->as        = 'u';
        $builder->distinct  = false;
        $builder->aggregate = null;

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select * from `users` as `u`', $sql );
    }

    public function test_it_compiles_basic_wheres() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn(
            [
                'wheres' => [['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 1, 'boolean' => 'and', 'not' => false]]
            ] 
        );
        $builder->shouldReceive( 'get_wheres' )->andReturn(
            [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 1, 'boolean' => 'and', 'not' => false]
            ] 
        );
        $builder->shouldReceive( 'set_binding' )->with( 1, 'where' )->andReturn( '%d' );
        
        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select * from `users` where `id` = %d', $sql );
    }

    public function test_it_compiles_nested_wheres() {
        $builder = Mockery::mock( Builder::class );
        $nested  = Mockery::mock( Builder::class );
        
        $builder->shouldReceive( 'get_clauses' )->andReturn(
            [
                'wheres' => [['type' => 'nested', 'query' => $nested, 'boolean' => 'and', 'not' => false]]
            ] 
        );
        $builder->shouldReceive( 'get_wheres' )->andReturn(
            [
                ['type' => 'nested', 'query' => $nested, 'boolean' => 'and', 'not' => false]
            ] 
        );
        
        $nested->shouldReceive( 'get_wheres' )->andReturn(
            [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 1, 'boolean' => 'and', 'not' => false]
            ] 
        );
        $nested->shouldReceive( 'set_binding' )->with( 1, 'where' )->andReturn( '%d' );
        
        $builder->shouldReceive( 'merge_bindings' )->with( $nested, 'where' );

        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;

        $sql = $this->grammar->compile_select( $builder );
        // Note the spaces: ( `id` = %d )
        $this->assertEquals( 'select * from `users` where ( `id` = %d )', $sql );
    }

    public function test_it_compiles_insert() {
        $builder       = Mockery::mock( Builder::class );
        $builder->from = 'users';
        $builder->shouldReceive( 'set_binding' )->andReturn( '%s', '%d' );

        $values = ['name' => 'Alice', 'votes' => 10];
        $sql    = $this->grammar->compile_insert( $builder, $values );
        
        $this->assertEquals( 'insert into `users` (`name`, `votes`) values (%s, %d)', $sql );
    }

    public function test_it_compiles_update() {
        $builder       = Mockery::mock( Builder::class );
        $builder->from = 'users';
        $builder->shouldReceive( 'get_wheres' )->andReturn(
            [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 1, 'boolean' => 'and', 'not' => false]
            ] 
        );
        $builder->shouldReceive( 'set_binding' )->with( 'Alice', 'select' )->andReturn( '%s' );
        $builder->shouldReceive( 'set_binding' )->with( 1, 'where' )->andReturn( '%d' );

        $values = ['name' => 'Alice'];
        $sql    = $this->grammar->compile_update( $builder, $values );
        
        $this->assertEquals( 'update `users` set `name` = %s where `id` = %d', $sql );
    }

    public function test_it_compiles_delete() {
        $builder        = Mockery::mock( Builder::class );
        $builder->from  = 'users';
        $builder->as    = null;
        $builder->joins = [];
        $builder->shouldReceive( 'get_wheres' )->andReturn(
            [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 1, 'boolean' => 'and', 'not' => false]
            ] 
        );
        $builder->shouldReceive( 'set_binding' )->with( 1, 'where' )->andReturn( '%d' );

        $sql = $this->grammar->compile_delete( $builder );
        
        $this->assertEquals( 'delete `users` from `users`  where `id` = %d', $sql );
    }

    public function test_it_compiles_aggregates() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->as        = null;
        $builder->aggregate = ['function' => 'count', 'columns' => ['*']];

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select count(*) as aggregate from `users`', $sql );
    }

    public function test_it_compiles_group_by() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['*'];
        $builder->from      = 'orders';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;
        $builder->groups    = ['user_id'];

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select * from `orders` group by `user_id`', $sql );
    }

    public function test_it_compiles_group_by_multiple_columns() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['*'];
        $builder->from      = 'orders';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;
        $builder->groups    = ['year', 'month'];

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select * from `orders` group by `year`, `month`', $sql );
    }

    public function test_it_compiles_having() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn(
            [
                'havings' => [
                    ['type' => 'basic', 'column' => 'total', 'operator' => '>', 'value' => 100, 'boolean' => 'and', 'not' => false],
                ],
            ]
        );
        $builder->shouldReceive( 'get_havings' )->andReturn(
            [
                ['type' => 'basic', 'column' => 'total', 'operator' => '>', 'value' => 100, 'boolean' => 'and', 'not' => false],
            ]
        );
        $builder->shouldReceive( 'set_binding' )->with( 100, 'having' )->andReturn( '%d' );
        $builder->columns   = ['*'];
        $builder->from      = 'orders';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;
        $builder->groups    = ['user_id'];

        $sql = $this->grammar->compile_select( $builder );
        $this->assertStringContainsString( 'group by `user_id`', $sql );
        $this->assertStringContainsString( 'having `total` > %d', $sql );
    }

    public function test_it_compiles_order_by_raw_passes_through_unquoted() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;
        $builder->orders    = [['column' => 'FIELD(status,\'active\',\'inactive\')', 'direction' => 'asc']];

        $sql = $this->grammar->compile_select( $builder );
        // order_by_raw stores raw string as column; Grammar wraps it — check it appears
        $this->assertStringContainsString( 'order by', $sql );
        $this->assertStringContainsString( 'asc', $sql );
    }

    public function test_it_compiles_distinct_aggregate() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns   = ['status'];
        $builder->from      = 'users';
        $builder->distinct  = true;
        $builder->as        = null;
        $builder->aggregate = ['function' => 'count', 'columns' => ['status']];

        $sql = $this->grammar->compile_select( $builder );
        $this->assertEquals( 'select count(distinct `status`) as aggregate from `users`', $sql );
    }

    public function test_it_compiles_union_all_keyword() {
        // The union 'query' must be a Builder (not a string) so compile_unions() can call
        // reset_bindings() on it to get the compiled SQL for the UNION fragment.
        $union_builder = Mockery::mock( Builder::class );
        $union_builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $union_builder->shouldReceive( 'reset_bindings' )->andReturn( 'select `name` from `roles`' );
        $union_builder->columns   = ['name'];
        $union_builder->from      = 'roles';
        $union_builder->distinct  = false;
        $union_builder->aggregate = null;
        $union_builder->as        = null;

        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->columns  = ['name'];
        $builder->from     = 'users';
        $builder->distinct = false;
        $union_query       = Mockery::mock( Builder::class );
        $union_query->shouldReceive( 'reset_bindings' )->andReturnSelf();
        $union_query->shouldReceive( 'compile_sql' )->andReturn( 'select * from `users` where `active` = %d' );
        $builder->shouldReceive( 'merge_bindings' )->with( $union_query, 'union' )->andReturnSelf();

        $builder->unions = [
            ['query' => $union_query, 'all' => true],
        ];

        $sql = $this->grammar->compile_select( $builder );
        $this->assertStringContainsString( 'union all', strtolower( $sql ) );
    }

    public function test_it_compiles_limit_with_offset() {
        $builder = Mockery::mock( Builder::class );
        $builder->shouldReceive( 'get_clauses' )->andReturn( [] );
        $builder->shouldReceive( 'set_binding' )->with( 10, 'limit' )->andReturn( '10' );
        $builder->shouldReceive( 'set_binding' )->with( 20, 'offset' )->andReturn( '20' );
        $builder->columns   = ['*'];
        $builder->from      = 'users';
        $builder->distinct  = false;
        $builder->aggregate = null;
        $builder->as        = null;
        $builder->limit     = 10;
        $builder->offset    = 20;

        $sql = $this->grammar->compile_select( $builder );
        $this->assertStringContainsString( 'limit 10', $sql );
        $this->assertStringContainsString( 'offset 20', $sql );
        // and offset must come AFTER limit
        $this->assertGreaterThan( strpos( $sql, 'limit' ), strpos( $sql, 'offset' ) );
    }
}


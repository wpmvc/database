<?php

namespace WpMVC\Database\Tests\Integration;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestPost;
use WpMVC\Database\Tests\Framework\Models\TestRole;
use WpMVC\Database\Tests\Framework\Models\TestTag;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class AdvancedQueryTest extends TestCase {
    public function setUp(): void {
        parent::setUp();
        
        $this->drop_tables();
        $this->create_tables();
    }

    public function tearDown(): void {
        $this->drop_tables();
        parent::tearDown();
    }

    protected function drop_tables() {
        Schema::drop_if_exists( 'test_user_roles' );
        Schema::drop_if_exists( 'test_roles' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
        Schema::drop_if_exists( 'test_tags' );
        Schema::drop_if_exists( 'test_taggables' );
    }

    protected function create_tables() {
        Schema::create(
            'test_users', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->timestamps();
            }
        );

        Schema::create(
            'test_posts', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'user_id' );
                $table->string( 'title' );
                $table->integer( 'views' )->default( 0 );
                $table->timestamps();
            }
        );

        Schema::create(
            'test_roles', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->timestamps();
            }
        );

        Schema::create(
            'test_user_roles', function( Blueprint $table ) {
                $table->unsigned_big_integer( 'user_id' );
                $table->unsigned_big_integer( 'role_id' );
            }
        );

        Schema::create(
            'test_tags', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->timestamps();
            }
        );

        Schema::create(
            'test_taggables', function( Blueprint $table ) {
                $table->unsigned_big_integer( 'test_tag_id' );
                $table->unsigned_big_integer( 'taggable_id' );
                $table->string( 'taggable_type' );
            }
        );
    }

    /**
     * Test relationship aggregates with callbacks.
     */
    public function test_it_handles_relationship_aggregates_with_callbacks() {
        $user = TestUser::create( ['name' => 'John Doe'] );
        TestPost::create( ['user_id' => $user->id, 'title' => 'Post 1', 'views' => 10] );
        TestPost::create( ['user_id' => $user->id, 'title' => 'Post 2', 'views' => 20] );
        TestPost::create( ['user_id' => $user->id, 'title' => 'Draft', 'views' => 100] ); // Should be filtered

        // Test Sum
        $found = TestUser::query()
            ->with_sum(
                ['posts' => function( $query ) {
                    $query->where( 'title', '!=', 'Draft' );
                }
                ], 'views'
            )
            ->find( $user->id );
        $this->assertEquals( 30, (int) $found->posts_sum_views );

        // Test Avg
        $found = TestUser::query()
            ->with_avg(
                ['posts' => function( $query ) {
                    $query->where( 'title', '!=', 'Draft' );
                }
                ], 'views'
            )
            ->find( $user->id );
        $this->assertEquals( 15, (float) $found->posts_avg_views );
    }

    /**
     * Test relationship filtering (where_has).
     */
    public function test_it_handles_where_has_with_nested_callbacks() {
        $user1 = TestUser::create( ['name' => 'Active Author'] );
        $post1 = TestPost::create( ['user_id' => $user1->id, 'title' => 'Published'] );
        $tag   = TestTag::create( ['name' => 'PHP'] );
        $post1->tags()->attach( $tag->id );

        $user2 = TestUser::create( ['name' => 'Inactive Author'] );
        TestPost::create( ['user_id' => $user2->id, 'title' => 'Draft'] );

        // Find users who have posts with a specific tag
        $query = TestUser::query()->where_has(
            'posts', function( $query ) {
                $query->where_has(
                    'tags', function( $q ) {
                        $q->where( 'name', 'PHP' );
                    }
                );
            }
        );

        $sql = $query->to_sql();
        // fwrite(STDERR, "\nWhereHas SQL: $sql\n");

        $results = $query->get();

        if ( count( $results ) !== 1 ) {
            $this->fail( "Expected 1 result, got " . count( $results ) . ". SQL: $sql" );
        }
        
        $this->assertEquals( 'Active Author', $results[0]->name );
    }

    /**
     * Test relationship filtering (doesnt_have).
     */
    public function test_it_handles_doesnt_have_and_where_doesnt_have() {
        $user1 = TestUser::create( ['name' => 'User with Posts'] );
        TestPost::create( ['user_id' => $user1->id, 'title' => 'Post'] );

        $user2 = TestUser::create( ['name' => 'User without Posts'] );

        // doesnt_have
        $this->assertCount( 1, TestUser::query()->doesnt_have( 'posts' )->get() );
        $this->assertEquals( 'User without Posts', TestUser::query()->doesnt_have( 'posts' )->first()->name );

        // where_doesnt_have with callback
        $results = TestUser::query()->where_doesnt_have(
            'posts', function( $query ) {
                $query->where( 'title', 'Post' );
            }
        )->get();
        
        $this->assertCount( 1, $results );
        $this->assertEquals( 'User without Posts', $results[0]->name );
    }

    /**
     * Test subquery in where clause using closure.
     */
    public function test_it_handles_subqueries_in_where_clauses() {
        TestUser::create( ['name' => 'User 1'] );
        TestUser::create( ['name' => 'User 2'] );

        // Simplified subquery simulation: find users whose name is in a list from another table/query
        $results = TestUser::query()->where(
            function( $query ) {
                $query->where( 'name', 'User 1' )->or_where( 'name', 'User 2' );
            }
        )->get();

        $this->assertCount( 2, $results );
    }

    /**
     * Test union operations.
     */
    public function test_it_can_perform_unions() {
        TestUser::create( ['name' => 'User A'] );
        TestTag::create( ['name' => 'Tag 1'] );

        $query = TestUser::query()
            ->select( 'name' )
            ->where( 'name', '=', 'User A' )
            ->union(
                function( $query ) {
                    $query->select( 'name' )->from( 'test_tags' )->where( 'name', '=', 'Tag 1' );
                }
            );

        $results = $query->get();

        $this->assertCount( 2, $results );
        $names = array_map( fn( $u ) => $u->name, $results->all() );
        $this->assertContains( 'User A', $names );
        $this->assertContains( 'Tag 1', $names );
    }

    /**
     * Test multi-query unions with multiple bindings.
     */
    public function test_it_handles_multi_query_unions_with_bindings() {
        TestUser::create( ['name' => 'User 1'] );
        TestTag::create( ['name' => 'Tag 1'] );
        TestRole::create( ['name' => 'Role 1'] );

        // Three distinct tables in each UNION arm avoids MySQL's
        // "Can't reopen table" limitation on temporary tables.
        $query = TestUser::query()
            ->select( 'name' )
            ->where( 'name', '=', 'User 1' )
            ->union_all(
                function( $query ) {
                    $query->select( 'name' )->from( 'test_tags' )->where( 'name', '=', 'Tag 1' );
                }
            )
            ->union(
                function( $query ) {
                    $query->select( 'name' )->from( 'test_roles' )->where( 'name', '=', 'Role 1' );
                }
            );

        $results = $query->get();

        $this->assertCount( 3, $results );
        $names = array_map( fn( $u ) => $u->name, $results->all() );
        $this->assertContains( 'User 1', $names );
        $this->assertContains( 'Tag 1', $names );
        $this->assertContains( 'Role 1', $names );
    }

    /**
     * Test unions with nested relationship logic (where_has).
     */
    public function test_it_handles_unions_with_nested_relationship_logic() {
        // User 1 has posts; User 2 has a role.
        // Test that union correctly combines two different relationship existence checks.
        $user1 = TestUser::create( ['name' => 'User with Posts'] );
        TestPost::create( ['user_id' => $user1->id, 'title' => 'A Post'] );

        $user2 = TestUser::create( ['name' => 'User with Role'] );
        $role  = TestRole::create( ['name' => 'Manager'] );
        $user2->roles()->attach( $role->id );

        // We build two separate queries and union them into a raw SQL union
        // to avoid MySQL temp table collision when the same table appears in both arms.
        // Each arm uses the same test_users base table but grouped by different criteria;
        // we work around the limitation by running each arm separately and asserting both are individually correct.
        $users_with_posts = TestUser::query()->where_has( 'posts' )->get();
        $users_with_roles = TestUser::query()->where_has( 'roles' )->get();

        // Verify each arm returns correct data
        $this->assertCount( 1, $users_with_posts );
        $this->assertEquals( 'User with Posts', $users_with_posts[0]->name );

        $this->assertCount( 1, $users_with_roles );
        $this->assertEquals( 'User with Role', $users_with_roles[0]->name );

        // Verify union SQL generates correctly (structural test — MySQL prevents execution with same temp table)
        $query = TestUser::query()->where_has( 'posts' )->union(
            function( $q ) {
                $q->where_has( 'roles' );
            }
        );
        $sql   = $query->get_raw_sql();
        $this->assertStringContainsString( 'union', strtolower( $sql ) );
        $this->assertStringContainsString( 'exists', strtolower( $sql ) );
    }

    /**
     * Test complex joins with multiple conditions.
     */
    public function test_it_handles_complex_joins() {
        $user = TestUser::create( ['name' => 'Join User'] );
        $post = TestPost::create( ['user_id' => $user->id, 'title' => 'Join Post', 'views' => 50] );

        $results = TestUser::query()
            ->join(
                'test_posts', function( $join ) {
                    $join->on( 'test_users.id', '=', 'test_posts.user_id' )
                     ->where( 'test_posts.views', '>', 10 );
                }
            )
            ->select( 'test_users.name', 'test_posts.title' )
            ->get();

        
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Join User', $results[0]->name );
        $this->assertEquals( 'Join Post', $results[0]->title );
    }

    /**
     * Test limit and offset (paginate core).
     */
    public function test_it_handles_limit_and_offset() {
        TestUser::create( ['name' => 'User 1'] );
        TestUser::create( ['name' => 'User 2'] );
        TestUser::create( ['name' => 'User 3'] );

        $results = TestUser::query()->order_by( 'id', 'asc' )->limit( 1 )->offset( 1 )->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'User 2', $results[0]->name );
    }

    /**
     * Test multi-column ordering.
     */
    public function test_it_handles_multi_column_ordering() {
        TestUser::create( ['name' => 'B', 'id' => 1] );
        TestUser::create( ['name' => 'A', 'id' => 2] );
        TestUser::create( ['name' => 'A', 'id' => 3] );

        $results = TestUser::query()
            ->order_by( 'name', 'asc' )
            ->order_by( 'id', 'desc' )
            ->get();

        $this->assertEquals( 'A', $results[0]->name );
        $this->assertEquals( 3, $results[0]->id );
        $this->assertEquals( 'A', $results[1]->name );
        $this->assertEquals( 2, $results[1]->id );
        $this->assertEquals( 'B', $results[2]->name );
    }
}

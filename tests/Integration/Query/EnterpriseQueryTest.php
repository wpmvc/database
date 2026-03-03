<?php

namespace WpMVC\Database\Tests\Integration\Query;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestPost;
use WpMVC\Database\Tests\Framework\Models\TestRole;
use WpMVC\Database\Tests\Framework\Models\TestTag;
use WpMVC\Database\Tests\Framework\Models\TestImage;
use WpMVC\Database\Tests\Framework\Models\TestProfile;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

/**
 * Enterprise-level query tests: GROUP BY+HAVING, batch INSERT, chunk/cursor,
 * transactions, DISTINCT+count, WHERE EXISTS, multi-col ORDER BY, correlated
 * subquery SELECT, chained with_count/aggregates, doesnt_have across all
 * relation types, and UNION+ORDER+LIMIT.
 */
class EnterpriseQueryTest extends TestCase {
    /** @var \WpMVC\Database\Tests\Framework\Models\TestPost */
    private $bob_post;

    public function setUp(): void {
        parent::setUp();
        $this->drop_tables();
        $this->create_tables();
        $this->seed_data();
    }

    public function tearDown(): void {
        $this->drop_tables();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Schema helpers
    // -------------------------------------------------------------------------

    protected function drop_tables(): void {
        Schema::drop_if_exists( 'test_taggables' );
        Schema::drop_if_exists( 'test_tags' );
        Schema::drop_if_exists( 'test_images' );
        Schema::drop_if_exists( 'test_user_roles' );
        Schema::drop_if_exists( 'test_roles' );
        Schema::drop_if_exists( 'test_profiles' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
    }

    protected function create_tables(): void {
        Schema::create(
            'test_users', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->string( 'name' );
                $t->timestamps();
            }
        );

        Schema::create(
            'test_posts', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->unsigned_big_integer( 'user_id' );
                $t->string( 'title' );
                $t->string( 'status' )->default( 'publish' );
                $t->integer( 'views' )->default( 0 );
                $t->timestamps();
            }
        );

        Schema::create(
            'test_profiles', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->unsigned_big_integer( 'user_id' );
                $t->string( 'bio' );
                $t->timestamps();
            }
        );

        Schema::create(
            'test_roles', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->string( 'name' );
                $t->timestamps();
            }
        );

        Schema::create(
            'test_user_roles', function( Blueprint $t ) {
                $t->unsigned_big_integer( 'user_id' );
                $t->unsigned_big_integer( 'role_id' );
            }
        );

        Schema::create(
            'test_images', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->string( 'url' );
                $t->unsigned_big_integer( 'imageable_id' );
                $t->string( 'imageable_type' );
                $t->timestamps();
            }
        );

        Schema::create(
            'test_tags', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->string( 'name' );
                $t->timestamps();
            }
        );

        Schema::create(
            'test_taggables', function( Blueprint $t ) {
                $t->unsigned_big_integer( 'test_tag_id' );
                $t->unsigned_big_integer( 'taggable_id' );
                $t->string( 'taggable_type' );
            }
        );
    }

    protected function seed_data(): void {
        // Users: Alice (2 posts, 1 profile, 2 roles, 2 images)
        //        Bob   (1 post, no profile, 1 role, 0 images)
        //        Charlie (no posts, no profile, no roles, no images)
        $alice   = TestUser::create( ['name' => 'Alice'] );
        $bob     = TestUser::create( ['name' => 'Bob'] );
        $charlie = TestUser::create( ['name' => 'Charlie'] ); // intentional no-relation user

        // Posts — reference by variable so seeding is ID-agnostic
        TestPost::create( ['user_id' => $alice->id, 'title' => 'Alice Post 1', 'status' => 'publish', 'views' => 100] );
        TestPost::create( ['user_id' => $alice->id, 'title' => 'Alice Post 2', 'status' => 'draft',   'views' => 50] );
        $this->bob_post = TestPost::create( ['user_id' => $bob->id, 'title' => 'Bob Post 1', 'status' => 'publish', 'views' => 200] );

        // Profile — only Alice
        TestProfile::create( ['user_id' => $alice->id, 'bio' => 'Alice Bio'] );

        // Roles
        $admin  = TestRole::create( ['name' => 'Admin'] );
        $editor = TestRole::create( ['name' => 'Editor'] );

        $alice->roles()->attach( $admin->id );
        $alice->roles()->attach( $editor->id );
        $bob->roles()->attach( $admin->id );

        // Images — morphMany on Alice only
        TestImage::create( ['url' => 'a1.jpg', 'imageable_id' => $alice->id, 'imageable_type' => TestUser::class] );
        TestImage::create( ['url' => 'a2.jpg', 'imageable_id' => $alice->id, 'imageable_type' => TestUser::class] );

        // Tags — morphToMany on Bob's post
        $php = TestTag::create( ['name' => 'PHP'] );
        $this->bob_post->tags()->attach( $php->id );
    }

    // =========================================================================
    // Group 1: GROUP BY + HAVING
    // =========================================================================

    public function test_group_by_having_with_has_many(): void {
        // Users with more than 1 post — use table alias not prefix
        $results = TestUser::query()
            ->join( 'test_posts', 'test_users.id', '=', 'test_posts.user_id' )
            ->select( 'test_users.id' )
            ->select( 'test_users.name' )
            ->add_select( ['post_count' => 'count(test_posts.id)'] )
            ->group_by( 'test_users.id' )
            ->having( 'post_count', '>', 1 )
            ->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'Alice', $results[0]->name );
        $this->assertEquals( 2, (int) $results[0]->post_count );
    }

    public function test_group_by_having_with_belongs_to_many(): void {
        // Roles attached to at least 2 users — use table alias not prefix
        $results = TestRole::query()
            ->join( 'test_user_roles', 'test_roles.id', '=', 'test_user_roles.role_id' )
            ->select( 'test_roles.name' )
            ->add_select( ['user_count' => 'count(test_user_roles.user_id)'] )
            ->group_by( 'test_roles.id' )
            ->having( 'user_count', '>=', 2 )
            ->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'Admin', $results[0]->name );
        $this->assertEquals( 2, (int) $results[0]->user_count );
    }

    // =========================================================================
    // Group 2: Batch INSERT
    // =========================================================================

    public function test_batch_insert_500_rows(): void {
        Schema::drop_if_exists( 'test_batch' );
        Schema::create(
            'test_batch', function( Blueprint $t ) {
                $t->big_increments( 'id' );
                $t->string( 'name' );
                $t->integer( 'score' );
            }
        );

        $rows = array_map( fn( $i ) => ['name' => "Item $i", 'score' => $i], range( 1, 500 ) );

        $builder = new Builder( new TestUser() );
        $builder->from( 'test_batch' )->insert( $rows );

        $count = ( new Builder( new TestUser() ) )->from( 'test_batch' )->count();
        $this->assertEquals( 500, $count );

        Schema::drop_if_exists( 'test_batch' );
    }

    // =========================================================================
    // Group 3: chunk() and cursor()
    // =========================================================================

    public function test_chunk_processes_all_users_in_batches(): void {
        $processed = 0;
        $pages     = 0;

        TestUser::query()->chunk(
            2, function( $batch, $page ) use ( &$processed, &$pages ) {
                $processed += $batch->count();
                $pages      = $page;
            }
        );

        $this->assertEquals( 3, $processed ); // Alice, Bob, Charlie
        $this->assertEquals( 2, $pages );      // page 1: 2 rows, page 2: 1 row
    }

    public function test_chunk_aborts_early_on_false_return(): void {
        $pages = 0;

        TestUser::query()->chunk(
            1, function( $batch, $page ) use ( &$pages ) {
                $pages = $page;
                return false; // stop immediately after first batch
            }
        );

        $this->assertEquals( 1, $pages );
    }

    public function test_chunk_eager_loads_has_many_in_each_batch(): void {
        $all_posts = [];

        TestUser::query()->with( 'posts' )->chunk(
            2, function( $batch ) use ( &$all_posts ) {
                foreach ( $batch as $user ) {
                    foreach ( $user->posts as $post ) {
                        $all_posts[] = $post->title;
                    }
                }
            }
        );

        $this->assertContains( 'Alice Post 1', $all_posts );
        $this->assertContains( 'Bob Post 1', $all_posts );
    }

    public function test_chunk_eager_loads_belongs_to_many_in_each_batch(): void {
        $role_names = [];

        TestUser::query()->with( 'roles' )->chunk(
            2, function( $batch ) use ( &$role_names ) {
                foreach ( $batch as $user ) {
                    foreach ( $user->roles as $role ) {
                        $role_names[] = $role->name;
                    }
                }
            }
        );

        $this->assertContains( 'Admin', $role_names );
        $this->assertContains( 'Editor', $role_names );
    }

    public function test_cursor_yields_all_rows_one_at_a_time(): void {
        $names = [];
        foreach ( TestUser::query()->cursor() as $user ) {
            $names[] = $user->name;
        }

        $this->assertCount( 3, $names );
        $this->assertContains( 'Alice', $names );
        $this->assertContains( 'Bob', $names );
        $this->assertContains( 'Charlie', $names );
    }

    // =========================================================================
    // Group 4: Transactions
    // =========================================================================

    public function test_transaction_commits_has_many_creation(): void {
        Builder::transaction(
            function() {
                $user = TestUser::create( ['name' => 'TX User'] );
                TestPost::create( ['user_id' => $user->id, 'title' => 'TX Post', 'status' => 'publish', 'views' => 0] );
            }
        );

        $found = TestUser::query()->where( 'name', 'TX User' )->first();
        $this->assertNotNull( $found );
        $this->assertCount( 1, $found->posts );
    }

    public function test_transaction_rolls_back_has_many_on_exception(): void {
        try {
            Builder::transaction(
                function() {
                    TestUser::create( ['name' => 'Rollback User'] );
                    throw new \RuntimeException( 'Forced rollback' );
                }
            );
        } catch ( \RuntimeException $e ) {
            // expected
        }

        $this->assertNull( TestUser::query()->where( 'name', 'Rollback User' )->first() );
    }

    public function test_transaction_rolls_back_belongs_to_many_on_exception(): void {
        $user_id = null;

        try {
            Builder::transaction(
                function() use ( &$user_id ) {
                    $user    = TestUser::create( ['name' => 'BTM Rollback'] );
                    $user_id = $user->id;
                    $role    = TestRole::create( ['name' => 'Temp Role'] );
                    $user->roles()->attach( $role->id );
                    throw new \RuntimeException( 'Forced rollback' );
                }
            );
        } catch ( \RuntimeException $e ) {
            // expected
        }

        $this->assertNull( TestUser::query()->find( $user_id ) );
    }

    public function test_transaction_rolls_back_morph_one_on_exception(): void {
        try {
            Builder::transaction(
                function() {
                    $user = TestUser::create( ['name' => 'Morph Rollback'] );
                    TestImage::create(
                        [
                            'url'            => 'morph.jpg',
                            'imageable_id'   => $user->id,
                            'imageable_type' => TestUser::class,
                        ]
                    );
                    throw new \RuntimeException( 'Forced rollback' );
                }
            );
        } catch ( \RuntimeException $e ) {
            // expected
        }

        $this->assertNull( TestUser::query()->where( 'name', 'Morph Rollback' )->first() );
    }

    // =========================================================================
    // Group 5: DISTINCT + count()
    // =========================================================================

    public function test_distinct_count_on_has_many_join(): void {
        // 3 post rows total (Alice×2, Bob×1) but only 2 distinct user authors
        $count = TestUser::query()
            ->join( 'test_posts', 'test_users.id', '=', 'test_posts.user_id' )
            ->distinct()
            ->count( 'test_users.id' );

        $this->assertEquals( 2, $count );
    }

    public function test_distinct_values_on_belongs_to_many(): void {
        // Both Alice and Bob have Admin — distinct query should return each role once
        $roles = TestRole::query()
            ->join( 'test_user_roles', 'test_roles.id', '=', 'test_user_roles.role_id' )
            ->distinct()
            ->select( 'test_roles.name' )
            ->order_by( 'test_roles.name' )
            ->get();

        $names = array_column( $roles->all(), 'name' );
        $this->assertEquals( $names, array_unique( $names ) );
        $this->assertContains( 'Admin', $names );
        $this->assertContains( 'Editor', $names );
    }

    public function test_distinct_with_where_filter(): void {
        // Posts have 'publish' and 'draft' statuses — distinct() should return 2 unique values
        $statuses = TestPost::query()
            ->distinct()
            ->select( 'status' )
            ->where( 'user_id', '>', 0 )
            ->order_by( 'status' )
            ->get();

        $values = array_column( $statuses->all(), 'status' );
        $this->assertEquals( count( $values ), count( array_unique( $values ) ) );
        $this->assertContains( 'publish', $values );
        $this->assertContains( 'draft', $values );
    }

    // =========================================================================
    // Group 6: WHERE EXISTS / NOT EXISTS
    // =========================================================================

    public function test_where_exists_with_has_many(): void {
        $results = TestUser::query()
            ->where_exists(
                function( $q ) {
                    $q->from( 'test_posts' )->where_column( 'test_posts.user_id', 'test_users.id' );
                }
            )
            ->order_by( 'name' )
            ->get();

        $this->assertCount( 2, $results ); // Alice and Bob
        $this->assertEquals( 'Alice', $results[0]->name );
        $this->assertEquals( 'Bob', $results[1]->name );
    }

    public function test_where_exists_with_belongs_to_many(): void {
        $results = TestUser::query()
            ->where_exists(
                function( $q ) {
                    $q->from( 'test_user_roles' )->where_column( 'test_user_roles.user_id', 'test_users.id' );
                }
            )
            ->order_by( 'name' )
            ->get();

        $this->assertCount( 2, $results ); // Alice and Bob — Charlie has no role
    }

    public function test_where_not_exists_with_has_one(): void {
        // Users without a profile (Bob and Charlie)
        $results = TestUser::query()
            ->where_not_exists(
                function( $q ) {
                    $q->from( 'test_profiles' )->where_column( 'test_profiles.user_id', 'test_users.id' );
                }
            )
            ->order_by( 'name' )
            ->get();

        $this->assertCount( 2, $results );
        $names = array_column( $results->all(), 'name' );
        $this->assertContains( 'Bob', $names );
        $this->assertContains( 'Charlie', $names );
    }

    public function test_where_not_exists_with_morph_many(): void {
        // Users without morph images (Bob and Charlie)
        $results = TestUser::query()
            ->where_not_exists(
                function( $q ) {
                    $q->from( 'test_images' )
                    ->where_column( 'test_images.imageable_id', 'test_users.id' )
                    ->where( 'test_images.imageable_type', TestUser::class );
                }
            )
            ->order_by( 'name' )
            ->get();

        $this->assertCount( 2, $results );
        $names = array_column( $results->all(), 'name' );
        $this->assertContains( 'Bob', $names );
        $this->assertContains( 'Charlie', $names );
    }

    // =========================================================================
    // Group 7: ORDER BY multi-column + raw
    // =========================================================================

    public function test_order_by_multiple_columns_on_has_many_result(): void {
        $results = TestPost::query()
            ->order_by( 'status', 'asc' )  // draft before publish
            ->order_by( 'views', 'desc' )  // higher views first within same status
            ->get();

        $this->assertEquals( 'draft', $results[0]->status );

        $publish_posts = array_values( array_filter( $results->all(), fn( $p ) => $p->status === 'publish' ) );
        $this->assertEquals( 200, (int) $publish_posts[0]->views ); // Bob 200
        $this->assertEquals( 100, (int) $publish_posts[1]->views ); // Alice 100
    }

    public function test_order_by_raw_field_function(): void {
        // FIELD() forces publish rows first, draft rows last
        $results = TestPost::query()
            ->order_by_raw( "FIELD(status, 'publish', 'draft')" )
            ->get();

        $this->assertEquals( 'publish', $results[0]->status );
        $this->assertEquals( 'publish', $results[1]->status );
        $this->assertEquals( 'draft', $results[2]->status );
    }

    // =========================================================================
    // Group 8: Subquery in SELECT (correlated computed column via where_raw SQL)
    // =========================================================================

    public function test_subquery_count_of_has_many_in_select(): void {
        // Count posts per user using JOIN + GROUP BY; use table alias not prefix
        $results = TestUser::query()
            ->select( 'test_users.name' )
            ->add_select( ['post_count' => 'count(test_posts.id)'] )
            ->join( 'test_posts', 'test_users.id', '=', 'test_posts.user_id' )
            ->group_by( 'test_users.id' )
            ->order_by( 'test_users.name' )
            ->get();

        $by_name = [];
        foreach ( $results as $row ) {
            $by_name[ $row->name ] = (int) $row->post_count;
        }

        $this->assertEquals( 2, $by_name['Alice'] );
        $this->assertEquals( 1, $by_name['Bob'] );
        // Charlie has no posts — excluded by INNER JOIN
    }

    public function test_subquery_count_of_belongs_to_many_in_select(): void {
        // Count roles per user using JOIN + GROUP BY; use table alias not prefix
        $results = TestUser::query()
            ->select( 'test_users.name' )
            ->add_select( ['role_count' => 'count(test_user_roles.role_id)'] )
            ->join( 'test_user_roles', 'test_users.id', '=', 'test_user_roles.user_id' )
            ->group_by( 'test_users.id' )
            ->order_by( 'test_users.name' )
            ->get();

        $by_name = [];
        foreach ( $results as $row ) {
            $by_name[ $row->name ] = (int) $row->role_count;
        }

        $this->assertEquals( 2, $by_name['Alice'] );   // Admin + Editor
        $this->assertEquals( 1, $by_name['Bob'] );     // Admin only
        // Charlie excluded by INNER JOIN (no roles)
    }

    // =========================================================================
    // Group 9: with_count / with_min / with_max / with_avg / with_sum chained
    // =========================================================================

    public function test_chained_aggregates_on_has_many(): void {
        // MySQL temp tables can't be reopened; run separate queries to avoid 'Can't reopen table'
        $users_count = TestUser::query()->with_count( 'posts' )->order_by( 'test_users.name' )->get();
        $users_min   = TestUser::query()->with_min( 'posts', 'views' )->order_by( 'test_users.name' )->get();
        $users_max   = TestUser::query()->with_max( 'posts', 'views' )->order_by( 'test_users.name' )->get();
        $users_sum   = TestUser::query()->with_sum( 'posts', 'views' )->order_by( 'test_users.name' )->get();

        $alice_count   = null;
        $charlie_count = null;
        foreach ( $users_count as $u ) {
            if ( $u->name === 'Alice' ) {
                $alice_count = $u; }
            if ( $u->name === 'Charlie' ) {
                $charlie_count = $u; }
        }

        $alice_min = null;
        $alice_max = null;
        $alice_sum = null;
        foreach ( $users_min as $u ) {
            if ( $u->name === 'Alice' ) {
                $alice_min = $u; } }
        foreach ( $users_max as $u ) {
            if ( $u->name === 'Alice' ) {
                $alice_max = $u; } }
        foreach ( $users_sum as $u ) {
            if ( $u->name === 'Alice' ) {
                $alice_sum = $u; } }

        $this->assertNotNull( $alice_count, 'Alice should be in results' );
        $this->assertEquals( 2, (int) $alice_count->posts_count );
        $this->assertEquals( 0, (int) $charlie_count->posts_count );
        $this->assertNotNull( $alice_min );
        $this->assertEquals( 50,  (int) $alice_min->posts_min_views );
        $this->assertEquals( 100, (int) $alice_max->posts_max_views );
        $this->assertEquals( 150, (int) $alice_sum->posts_sum_views );
    }

    public function test_with_count_on_belongs_to_many(): void {
        $users = TestUser::query()->with_count( 'roles' )->order_by( 'test_users.name' )->get();

        $alice   = null;
        $bob     = null;
        $charlie = null;
        foreach ( $users as $u ) {
            if ( $u->name === 'Alice' ) {
                $alice = $u; }
            if ( $u->name === 'Bob' ) {
                $bob = $u; }
            if ( $u->name === 'Charlie' ) {
                $charlie = $u; }
        }

        $this->assertEquals( 2, (int) $alice->roles_count );   // Admin + Editor
        $this->assertEquals( 1, (int) $bob->roles_count );     // Admin only
        $this->assertEquals( 0, (int) $charlie->roles_count ); // no roles
    }

    public function test_with_count_on_morph_many(): void {
        $users = TestUser::query()->with_count( 'images' )->order_by( 'test_users.name' )->get();

        $alice = null;
        $bob   = null;
        foreach ( $users as $u ) {
            if ( $u->name === 'Alice' ) {
                $alice = $u; }
            if ( $u->name === 'Bob' ) {
                $bob = $u; }
        }

        $this->assertEquals( 2, (int) $alice->images_count );
        $this->assertEquals( 0, (int) $bob->images_count );
    }

    public function test_with_count_on_morph_to_many(): void {
        $posts = TestPost::query()->with_count( 'tags' )->order_by( 'test_posts.title' )->get();

        $bob_post = null;
        $alice_p1 = null;
        foreach ( $posts as $p ) {
            if ( $p->title === 'Bob Post 1' ) {
                $bob_post = $p; }
            if ( $p->title === 'Alice Post 1' ) {
                $alice_p1 = $p; }
        }

        $this->assertNotNull( $bob_post, 'Bob Post 1 should exist' );
        $this->assertEquals( 1, (int) $bob_post->tags_count );
        $this->assertEquals( 0, (int) $alice_p1->tags_count );
    }

    // =========================================================================
    // Group 10: doesnt_have across relations
    // =========================================================================

    public function test_doesnt_have_with_has_one(): void {
        // Bob and Charlie have no profile
        $results = TestUser::query()->doesnt_have( 'profile' )->order_by( 'name' )->get();

        $this->assertCount( 2, $results );
        $names = array_column( $results->all(), 'name' );
        $this->assertContains( 'Bob', $names );
        $this->assertContains( 'Charlie', $names );
    }

    public function test_doesnt_have_with_morph_to_many(): void {
        // Alice Post 1 and Alice Post 2 have no tags
        $results = TestPost::query()->doesnt_have( 'tags' )->order_by( 'title' )->get();

        $this->assertCount( 2, $results );
        $titles = array_column( $results->all(), 'title' );
        $this->assertContains( 'Alice Post 1', $titles );
        $this->assertContains( 'Alice Post 2', $titles );
    }

    public function test_doesnt_have_with_belongs_to_many(): void {
        // Charlie has no role
        $results = TestUser::query()->doesnt_have( 'roles' )->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'Charlie', $results[0]->name );
    }

    // =========================================================================
    // Group 11: UNION + ORDER BY + LIMIT
    // =========================================================================

    public function test_union_order_and_limit_combined(): void {
        // Union user names with role names — order alphabetically
        $q1 = TestUser::query()->select( 'name' )->where( 'name', 'Alice' );
        $q2 = TestRole::query()->select( 'name' )->where( 'name', 'Admin' );

        // ORDER BY and LIMIT are applied to the full UNION result, not q1
        $results = $q1->union( $q2 )->order_by( 'name', 'asc' )->get();

        $names = array_column( $results->all(), 'name' );
        $this->assertContains( 'Alice', $names );
        $this->assertContains( 'Admin', $names );

        // Verify alphabetical order: Admin < Alice
        $this->assertEquals( 'Admin', $names[0] );
        $this->assertEquals( 'Alice', $names[1] );
    }

    public function test_union_with_limit_returns_top_n(): void {
        // Union all users with all roles, limit to 2 alphabetically first
        $q1 = TestUser::query()->select( 'name' );
        $q2 = TestRole::query()->select( 'name' );

        $results = $q1->union( $q2 )->order_by( 'name', 'asc' )->limit( 2 )->get();

        $this->assertCount( 2, $results );
        // Alphabetically: Admin, Alice, Bob, Charlie, Editor → top 2 are Admin, Alice
        $this->assertEquals( 'Admin', $results[0]->name );
        $this->assertEquals( 'Alice', $results[1]->name );
    }
}

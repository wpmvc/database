<?php

namespace WpMVC\Database\Tests\Integration\Eloquent\Relations;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestPost;
use WpMVC\Database\Tests\Framework\Models\TestImage;
use WpMVC\Database\Tests\Framework\Models\TestRole;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class AdvancedRelationshipTest extends TestCase {
    public function setUp(): void {
        parent::setUp();
        
        // Reset morph map
        Model::morph_map( [], false );

        // Setup tables
        Schema::drop_if_exists( 'test_user_roles' );
        Schema::drop_if_exists( 'test_roles' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
        Schema::drop_if_exists( 'test_images' );

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
            'test_images', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'url' );
                $table->unsigned_big_integer( 'imageable_id' );
                $table->string( 'imageable_type' );
                $table->timestamps();
            } 
        );
    }

    public function tearDown(): void {
        Schema::drop_if_exists( 'test_user_roles' );
        Schema::drop_if_exists( 'test_roles' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
        Schema::drop_if_exists( 'test_images' );
        parent::tearDown();
    }

    public function test_it_can_use_custom_morph_map() {
        Model::morph_map(
            [
                'user_alias' => TestUser::class,
                'post_alias' => TestPost::class,
            ]
        );

        $user  = TestUser::create( ['name' => 'Morph User'] );
        $image = TestImage::create(
            [
                'url'            => 'avatar.jpg',
                'imageable_id'   => $user->id,
                'imageable_type' => 'user_alias',
            ]
        );

        $found_image = TestImage::find( $image->id );
        $this->assertInstanceOf( TestUser::class, $found_image->imageable );
        $this->assertEquals( 'Morph User', $found_image->imageable->name );
        
        $this->assertEquals( 'user_alias', $user->get_morph_class() );
    }

    public function test_it_can_access_pivot_data() {
        $user = TestUser::create( ['name' => 'Role User'] );
        $role = TestRole::create( ['name' => 'Manager'] );

        $user->roles()->attach( $role->id );

        $found_user = TestUser::with( 'roles' )->find( $user->id );
        $found_role = $found_user->roles->first();

        $this->assertEquals( $user->id, $found_role->pivot_user_id );
    }

    public function test_it_prevents_circular_recursion_in_to_array() {
        $user = TestUser::create( ['name' => 'Circular User'] );
        $post = TestPost::create( ['title' => 'Circular Post', 'user_id' => $user->id] );

        $user->set_relation( 'latest_post', $post );
        $post->set_relation( 'author', $user );

        $array = $user->to_array();
        
        $this->assertEquals( 'Circular User', $array['name'] );
        $this->assertEquals( 'Circular Post', $array['latest_post']['title'] );
        $this->assertEmpty( $array['latest_post']['author'], "Circular relation 'author' should be an empty array to prevent loop." );
    }

    public function test_it_handles_null_morph_to() {
        $image = TestImage::create(
            [
                'url'            => 'orphan.jpg',
                'imageable_id'   => 0,
                'imageable_type' => 'Unknown',
            ]
        );

        $this->assertNull( $image->imageable );
    }

    // =========================================================================
    // HasOneOfMany — latest() / oldest() patterns
    // =========================================================================

    public function test_has_one_of_many_returns_latest_by_id(): void {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'First Post', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'Second Post', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'Third Post', 'user_id' => $user->id] );

        $latest = $user->latest_post;

        $this->assertInstanceOf( TestPost::class, $latest );
        $this->assertEquals( 'Third Post', $latest->title );
    }

    public function test_has_one_of_many_returns_oldest_by_id(): void {
        $user = TestUser::create( ['name' => 'Blogger'] );
        TestPost::create( ['title' => 'Alpha', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'Beta', 'user_id' => $user->id] );

        $oldest = $user->oldest_post;

        $this->assertInstanceOf( TestPost::class, $oldest );
        $this->assertEquals( 'Alpha', $oldest->title );
    }

    public function test_has_one_of_many_returns_null_when_no_related(): void {
        $user   = TestUser::create( ['name' => 'NoPost User'] );
        $latest = $user->latest_post;
        $this->assertNull( $latest );
    }

    public function test_has_one_of_many_isolates_per_parent(): void {
        $user_a = TestUser::create( ['name' => 'User A'] );
        $user_b = TestUser::create( ['name' => 'User B'] );

        TestPost::create( ['title' => 'A-First', 'user_id' => $user_a->id] );
        TestPost::create( ['title' => 'A-Last', 'user_id' => $user_a->id] );
        TestPost::create( ['title' => 'B-Only', 'user_id' => $user_b->id] );

        $this->assertEquals( 'A-Last', $user_a->latest_post->title );
        $this->assertEquals( 'B-Only', $user_b->latest_post->title );
    }

    public function test_has_one_of_many_eager_loads_correctly(): void {
        $user_a = TestUser::create( ['name' => 'Eager A'] );
        $user_b = TestUser::create( ['name' => 'Eager B'] );

        TestPost::create( ['title' => 'A-Old', 'user_id' => $user_a->id] );
        TestPost::create( ['title' => 'A-New', 'user_id' => $user_a->id] );
        TestPost::create( ['title' => 'B-Post', 'user_id' => $user_b->id] );

        $users = TestUser::with( 'latest_post' )
            ->where_in( 'id', [$user_a->id, $user_b->id] )
            ->order_by( 'id' )
            ->get();

        $this->assertTrue( $users[0]->relation_loaded( 'latest_post' ) );
        $this->assertEquals( 'A-New', $users[0]->latest_post->title );
        $this->assertEquals( 'B-Post', $users[1]->latest_post->title );
    }

    // =========================================================================
    // HasOneOrMany::create() and make() fixes
    // =========================================================================

    public function test_make_builds_instance_without_saving(): void {
        $user = TestUser::create( ['name' => 'Maker'] );

        $post = $user->posts()->make( ['title' => 'Draft Post'] );

        // Not persisted
        $this->assertFalse( $post->exists );
        $this->assertNull( $post->id );
        // FK is correctly injected
        $this->assertEquals( $user->id, $post->user_id );
        // Nothing written to DB
        $this->assertCount( 0, TestPost::where( 'user_id', $user->id )->get() );
    }

    public function test_make_does_not_require_parent_to_be_saved(): void {
        // make() should work even on an unsaved parent (unlike create())
        $user = new TestUser( ['name' => 'Ghost'] );
        $this->assertFalse( $user->exists );

        $post = $user->posts()->make( ['title' => 'Ghost Draft'] );
        $this->assertFalse( $post->exists );
    }

    public function test_create_throws_when_parent_not_saved(): void {
        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessageMatches( '/has not been saved yet/' );

        $user = new TestUser( ['name' => 'Unsaved'] );
        // Parent not persisted → must throw
        $user->posts()->create( ['title' => 'Should Fail'] );
    }

    public function test_create_returns_saved_model_with_fk(): void {
        $user = TestUser::create( ['name' => 'Creator'] );
        $post = $user->posts()->create( ['title' => 'Saved Post'] );

        $this->assertInstanceOf( TestPost::class, $post );
        $this->assertTrue( $post->exists );
        $this->assertNotNull( $post->id );
        $this->assertEquals( $user->id, $post->user_id );

        // Verify it's actually in the DB
        $found = TestPost::find( $post->id );
        $this->assertNotNull( $found );
        $this->assertEquals( 'Saved Post', $found->title );
    }

    public function test_create_fk_overrides_caller_supplied_value(): void {
        $user_a = TestUser::create( ['name' => 'Owner'] );
        $user_b = TestUser::create( ['name' => 'Intruder'] );

        // Caller tries to set user_id = user_b->id, but the relation must override it
        $post = $user_a->posts()->create(
            ['title' => 'Protected', 'user_id' => $user_b->id]
        );

        $this->assertEquals( $user_a->id, $post->user_id );
    }
}

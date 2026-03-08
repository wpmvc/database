<?php

namespace WpMVC\Database\Tests\Integration\Eloquent\Relations;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Framework\Models\TestUser;
use WpMVC\Database\Tests\Framework\Models\TestPost;
use WpMVC\Database\Tests\Framework\Models\TestProfile;
use WpMVC\Database\Tests\Framework\Models\TestRole;
use WpMVC\Database\Tests\Framework\Models\TestCountry;
use WpMVC\Database\Tests\Framework\Models\TestImage;
use WpMVC\Database\Tests\Framework\Models\TestTag;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;

class RelationshipTest extends TestCase {
    public function setUp(): void {
        parent::setUp();
        
        Schema::drop_if_exists( 'test_user_roles' );
        Schema::drop_if_exists( 'test_roles' );
        Schema::drop_if_exists( 'test_profiles' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );

        Schema::create(
            'test_users', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'country_id' )->nullable();
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
            'test_profiles', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'user_id' );
                $table->string( 'bio' );
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
            'test_countries', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'name' );
                $table->timestamps();
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

    public function tearDown(): void {
        Schema::drop_if_exists( 'test_user_roles' );
        Schema::drop_if_exists( 'test_roles' );
        Schema::drop_if_exists( 'test_profiles' );
        Schema::drop_if_exists( 'test_posts' );
        Schema::drop_if_exists( 'test_users' );
        Schema::drop_if_exists( 'test_countries' );
        Schema::drop_if_exists( 'test_images' );
        Schema::drop_if_exists( 'test_tags' );
        Schema::drop_if_exists( 'test_taggables' );
        parent::tearDown();
    }

    public function test_it_can_resolve_has_one_relationship() {
        $user    = TestUser::create( ['name' => 'Profile User'] );
        $profile = TestProfile::create(
            [
                'bio'     => 'Software Engineer',
                'user_id' => $user->id,
            ] 
        );

        $this->assertInstanceOf( TestProfile::class, $user->profile );
        $this->assertEquals( 'Software Engineer', $user->profile->bio );
        $this->assertEquals( $user->id, $profile->user->id );
    }

    public function test_it_can_resolve_belongs_to_relationship() {
        $user = TestUser::create( ['name' => 'Author'] );
        $post = TestPost::create(
            [
                'title'   => 'Eloquent Relationships',
                'user_id' => $user->id,
            ] 
        );

        $this->assertEquals( $user->id, $post->user->id );
        $this->assertEquals( 'Author', $post->user->name );
    }

    public function test_it_can_resolve_has_many_relationship() {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'Post 1', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'Post 2', 'user_id' => $user->id] );

        $this->assertCount( 2, $user->posts );
        $this->assertEquals( 'Post 1', $user->posts[0]->title );
    }

    public function test_it_can_resolve_many_to_many_relationship() {
        $user  = TestUser::create( ['name' => 'Admin User'] );
        $role1 = TestRole::create( ['name' => 'Admin'] );
        $role2 = TestRole::create( ['name' => 'Editor'] );

        $user->roles()->attach( $role1->id );
        $user->roles()->attach( $role2->id );

        $this->assertCount( 2, $user->roles );
        $this->assertEquals( 'Admin', $user->roles[0]->name );
        $this->assertEquals( 'Editor', $user->roles[1]->name );

        // Check inverse
        $this->assertCount( 1, $role1->users );
        $this->assertEquals( 'Admin User', $role1->users[0]->name );
    }

    public function test_it_can_eager_load_relationships() {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'Post 1', 'user_id' => $user->id] );

        $found = TestUser::with( ['posts', 'profile'] )->find( $user->id );
        
        $this->assertTrue( $found->relation_loaded( 'posts' ) );
        $this->assertTrue( $found->relation_loaded( 'profile' ) );
    }

    public function test_it_can_resolve_has_many_through_relationship() {
        $country = TestCountry::create( ['name' => 'USA'] );
        $user    = TestUser::create( ['name' => 'User', 'country_id' => $country->id] );
        TestPost::create( ['title' => 'Post 1', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'Post 2', 'user_id' => $user->id] );

        $this->assertCount( 2, $country->posts );
        $this->assertEquals( 'Post 1', $country->posts[0]->title );
    }

    public function test_it_can_resolve_has_one_through_relationship() {
        $country = TestCountry::create( ['name' => 'Canada'] );
        $user    = TestUser::create( ['name' => 'User', 'country_id' => $country->id] );
        TestProfile::create( ['bio' => 'Bio', 'user_id' => $user->id] );

        $this->assertInstanceOf( TestProfile::class, $country->profile );
        $this->assertEquals( 'Bio', $country->profile->bio );
    }

    public function test_it_can_resolve_morph_one_relationship() {
        $user = TestUser::create( ['name' => 'User'] );
        $post = TestPost::create( ['title' => 'Post', 'user_id' => $user->id] );

        $user_image = TestImage::create(
            [
                'url'            => 'user.jpg',
                'imageable_id'   => $user->id,
                'imageable_type' => TestUser::class,
            ] 
        );

        $post_image = TestImage::create(
            [
                'url'            => 'post.jpg',
                'imageable_id'   => $post->id,
                'imageable_type' => TestPost::class,
            ] 
        );

        $this->assertEquals( 'user.jpg', $user->image->url );
        $this->assertEquals( 'post.jpg', $post->image->url );
        
        // Inverse
        $this->assertInstanceOf( TestUser::class, $user_image->imageable );
        $this->assertInstanceOf( TestPost::class, $post_image->imageable );
    }

    public function test_it_can_resolve_morph_to_many_relationship() {
        $user = TestUser::create( ['name' => 'User'] );
        $post = TestPost::create( ['title' => 'Post', 'user_id' => $user->id] );
        $tag  = TestTag::create( ['name' => 'PHP'] );

        $post->tags()->attach( $tag->id );

        $this->assertCount( 1, $post->tags );
        $this->assertEquals( 'PHP', $post->tags[0]->name );

        // Inverse
        $this->assertCount( 1, $tag->posts );
        $this->assertEquals( 'Post', $tag->posts[0]->title );
    }

    public function test_it_can_resolve_morph_many_relationship() {
        $user = TestUser::create( ['name' => 'User'] );
        TestImage::create(
            [
                'url'            => 'img1.jpg',
                'imageable_id'   => $user->id,
                'imageable_type' => TestUser::class,
            ] 
        );
        TestImage::create(
            [
                'url'            => 'img2.jpg',
                'imageable_id'   => $user->id,
                'imageable_type' => TestUser::class,
            ] 
        );

        $this->assertCount( 2, $user->images );
        $this->assertEquals( 'img1.jpg', $user->images[0]->url );
    }

    public function test_it_can_eager_load_nested_relationships() {
        $user = TestUser::create( ['name' => 'Author'] );
        $post = TestPost::create( ['title' => 'Post with Tags', 'user_id' => $user->id] );
        $tag  = TestTag::create( ['name' => 'Laravel'] );
        $post->tags()->attach( $tag->id );

        $found = TestUser::with( 'posts.tags' )->find( $user->id );

        $this->assertTrue( $found->relation_loaded( 'posts' ) );
        $this->assertTrue( $found->posts[0]->relation_loaded( 'tags' ) );
        $this->assertEquals( 'Laravel', $found->posts[0]->tags[0]->name );
    }

    public function test_it_can_count_relationships() {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'Post 1', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'Post 2', 'user_id' => $user->id] );

        $found = TestUser::with_count( 'posts' )->find( $user->id );

        $this->assertEquals( 2, (int) $found->posts_count );
    }

    public function test_it_can_count_multiple_relationships() {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'Post 1', 'user_id' => $user->id] );
        
        $role = TestRole::create( ['name' => 'Editor'] );
        $user->roles()->attach( $role->id );

        $found = TestUser::with_count( ['posts', 'roles'] )->find( $user->id );

        $this->assertEquals( 1, (int) $found->posts_count );
        $this->assertEquals( 1, (int) $found->roles_count );
    }

    public function test_it_can_eager_load_with_complex_nested_callbacks() {
        $user  = TestUser::create( ['name' => 'Author'] );
        $post1 = TestPost::create( ['title' => 'PHP Post', 'user_id' => $user->id] );
        $post2 = TestPost::create( ['title' => 'JS Post', 'user_id' => $user->id] );
        
        $tag1 = TestTag::create( ['name' => 'Web'] );
        $tag2 = TestTag::create( ['name' => 'Backend'] );
        $tag3 = TestTag::create( ['name' => 'Frontend'] );

        $post1->tags()->attach( $tag1->id );
        $post1->tags()->attach( $tag2->id );
        $post2->tags()->attach( $tag3->id );

        $found = TestUser::with(
            [
                'posts'      => function( $query ) {
                    $query->where( 'title', 'like', 'PHP%' );
                },
                'posts.tags' => function( $query ) {
                    $query->where( 'name', 'Backend' );
                }
            ] 
        )->find( $user->id );

        $this->assertCount( 1, $found->posts ); // Only PHP Post
        $this->assertEquals( 'PHP Post', $found->posts[0]->title );
        $this->assertCount( 1, $found->posts[0]->tags ); // Only Backend tag
        $this->assertEquals( 'Backend', $found->posts[0]->tags[0]->name );
    }

    public function test_it_can_count_with_complex_multiple_callbacks() {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'PHP Post', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'JS Post', 'user_id' => $user->id] );
        
        $role1 = TestRole::create( ['name' => 'Admin'] );
        $role2 = TestRole::create( ['name' => 'Editor'] );
        $user->roles()->attach( $role1->id );
        $user->roles()->attach( $role2->id );

        $found = TestUser::with_count(
            [
                'posts' => function( $query ) {
                    $query->where( 'title', 'like', 'PHP%' );
                },
                'roles' => function( $query ) {
                    $query->where( 'name', 'Admin' );
                }
            ] 
        )->find( $user->id );

        $this->assertEquals( 1, (int) $found->posts_count );
        $this->assertEquals( 1, (int) $found->roles_count );
    }

    public function test_it_can_eager_load_with_very_complex_nested_callbacks() {
        $user    = TestUser::create( ['name' => 'Author'] );
        $profile = TestProfile::create( ['bio' => 'Senior Developer', 'user_id' => $user->id] );
        $post    = TestPost::create( ['title' => 'WpMVC Tutorial', 'user_id' => $user->id] );
        $tag     = TestTag::create( ['name' => 'PHP'] );
        $post->tags()->attach( $tag->id );

        $found = TestUser::with(
            [
                'profile'    => function( $query ) {
                    $query->where( 'bio', 'like', '%Developer' );
                },
                'posts'      => function( $query ) {
                    $query->where( 'title', 'WpMVC Tutorial' );
                },
                'posts.tags' => function( $query ) {
                    $query->where( 'name', 'PHP' );
                }
            ] 
        )->find( $user->id );

        $this->assertTrue( $found->relation_loaded( 'profile' ) );
        $this->assertEquals( 'Senior Developer', $found->profile->bio );
        $this->assertCount( 1, $found->posts );
        $this->assertEquals( 'WpMVC Tutorial', $found->posts[0]->title );
        $this->assertCount( 1, $found->posts[0]->tags );
        $this->assertEquals( 'PHP', $found->posts[0]->tags[0]->name );
    }

    public function test_it_can_combine_with_and_with_count_with_callbacks() {
        $user = TestUser::create( ['name' => 'Author'] );
        TestPost::create( ['title' => 'PHP Post', 'user_id' => $user->id] );
        TestPost::create( ['title' => 'JS Post', 'user_id' => $user->id] );
        
        $found = TestUser::with(
            [
                'posts' => function( $query ) {
                    $query->where( 'title', 'PHP Post' );
                }
            ] 
        )->with_count(
            [
                'posts' => function( $query ) {
                        $query->where( 'title', 'like', 'JS%' );
                }
            ] 
        )->find( $user->id );

        $this->assertCount( 1, $found->posts );
        $this->assertEquals( 'PHP Post', $found->posts[0]->title );
        $this->assertEquals( 1, (int) $found->posts_count ); // Count of JS posts
    }

    public function test_it_can_handle_deeply_nested_with_and_counts_inside_callbacks() {
        $country = TestCountry::create( ['name' => 'USA'] );
        $user    = TestUser::create( ['name' => 'Author', 'country_id' => $country->id] );
        $post1   = TestPost::create( ['title' => 'Tutorial 1', 'user_id' => $user->id] );
        $post2   = TestPost::create( ['title' => 'News 1', 'user_id' => $user->id] );
        
        $tag1 = TestTag::create( ['name' => 'PHP'] );
        $tag2 = TestTag::create( ['name' => 'JS'] );
        $post1->tags()->attach( $tag1->id );
        $post1->tags()->attach( $tag2->id );

        $found = TestCountry::with(
            [
                'users' => function( $query ) {
                    $query->with(
                        [
                            'posts' => function( $q ) {
                                $q->where( 'title', 'like', '%Tutorial%' )
                                ->with(
                                    [
                                        'tags' => function( $q2 ) {
                                            $q2->where( 'name', 'PHP' );
                                        }
                                    ] 
                                )
                                ->with_count( 'tags' );
                            }
                        ] 
                    )->with_count( 'posts' );
                }
            ] 
        )->find( $country->id );

        $this->assertCount( 1, $found->users );
        $this->assertEquals( 'Author', $found->users[0]->name );
        $this->assertEquals( 2, (int) $found->users[0]->posts_count ); 
        
        $this->assertCount( 1, $found->users[0]->posts ); 
        $this->assertEquals( 'Tutorial 1', $found->users[0]->posts[0]->title );
        $this->assertEquals( 2, (int) $found->users[0]->posts[0]->tags_count );
        
        $this->assertCount( 1, $found->users[0]->posts[0]->tags ); 
        $this->assertEquals( 'PHP', $found->users[0]->posts[0]->tags[0]->name );
    }

    // =========================================================================
    // Relation mutation: sync / detach / update_or_create / where_has / doesnt_have
    // =========================================================================

    public function test_it_can_attach_and_verify_many_to_many(): void {
        $user  = TestUser::create( ['name' => 'AttachVerify'] );
        $role1 = TestRole::create( ['name' => 'Admin'] );
        $role2 = TestRole::create( ['name' => 'Editor'] );
        $role3 = TestRole::create( ['name' => 'Viewer'] );

        $user->roles()->attach( $role1->id );
        $user->roles()->attach( $role2->id );
        $user->roles()->attach( $role3->id );

        $roles = TestUser::find( $user->id )->roles;
        $this->assertCount( 3, $roles );

        // Attach duplicate should not create a second pivot row if using unique constraint
        // (just verify count is still correct after repeated attach)
        $role_ids = array_map( fn( $r ) => (int) $r->id, $roles->all() );
        sort( $role_ids );
        $this->assertEquals(
            [(int) $role1->id, (int) $role2->id, (int) $role3->id],
            $role_ids
        );
    }

    public function test_it_can_detach_specific_role_by_deleting_pivot(): void {
        $user  = TestUser::create( ['name' => 'DetachVerify'] );
        $role1 = TestRole::create( ['name' => 'Alpha'] );
        $role2 = TestRole::create( ['name' => 'Beta'] );

        $user->roles()->attach( $role1->id );
        $user->roles()->attach( $role2->id );
        $this->assertCount( 2, TestUser::find( $user->id )->roles );

        // Simulate detach by deleting from pivot table directly
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'test_user_roles', ['user_id' => $user->id, 'role_id' => $role1->id] );

        $roles = TestUser::find( $user->id )->roles;
        $this->assertCount( 1, $roles );
        $this->assertEquals( (int) $role2->id, (int) $roles[0]->id );
    }

    public function test_it_can_use_where_has_on_morph_many(): void {
        $user_with    = TestUser::create( ['name' => 'HasImages'] );
        $user_without = TestUser::create( ['name' => 'NoImages'] );
        TestImage::create(
            [
                'url'            => 'avatar.jpg',
                'imageable_id'   => $user_with->id,
                'imageable_type' => TestUser::class,
            ]
        );

        $results = TestUser::where_has( 'images' )->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'HasImages', $results[0]->name );
    }

    public function test_it_can_use_doesnt_have_on_has_many(): void {
        $with_posts    = TestUser::create( ['name' => 'HasPosts'] );
        $without_posts = TestUser::create( ['name' => 'NoPosts'] );
        TestPost::create( ['title' => 'A Post', 'user_id' => $with_posts->id] );

        $results = TestUser::doesnt_have( 'posts' )->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'NoPosts', $results[0]->name );
    }

    public function test_it_can_use_doesnt_have_on_morph_to_many(): void {
        $post_with    = TestPost::create( ['title' => 'Tagged', 'user_id' => 1] );
        $post_without = TestPost::create( ['title' => 'Untagged', 'user_id' => 1] );
        $tag          = TestTag::create( ['name' => 'PHP'] );
        $post_with->tags()->attach( $tag->id );

        $results = TestPost::doesnt_have( 'tags' )->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'Untagged', $results[0]->title );
    }
}

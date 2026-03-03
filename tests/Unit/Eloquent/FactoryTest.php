<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Eloquent\Factory;
use WpMVC\Database\Eloquent\Collection;
use WpMVC\Database\Eloquent\Sequence;
use WpMVC\Database\Tests\Framework\Mocks\User;

class UserFactory extends Factory {
    protected $model = User::class;

    public function definition(): array {
        return [
            'name'  => $this->faker()->name(),
            'email' => $this->faker()->safe_email(),
            'role'  => 'user',
        ];
    }

    public function admin() {
        return $this->state(
            [
                'role' => 'admin',
            ] 
        );
    }
}

class FactoryTest extends TestCase {
    public function test_it_can_make_a_single_instance() {
        $user = UserFactory::new()->make();

        $this->assertInstanceOf( User::class, $user );
        $this->assertIsString( $user->name );
        $this->assertIsString( $user->email );
        $this->assertEquals( 'user', $user->role );
        $this->assertFalse( $user->exists );
    }

    public function test_it_can_make_multiple_instances() {
        $users = UserFactory::new()->count( 3 )->make();

        $this->assertInstanceOf( Collection::class, $users );
        $this->assertCount( 3, $users );
        foreach ( $users as $user ) {
            $this->assertInstanceOf( User::class, $user );
        }
    }

    public function test_it_can_apply_states() {
        $user = UserFactory::new()->admin()->make();

        $this->assertEquals( 'admin', $user->role );
    }

    public function test_it_can_override_attributes() {
        $user = UserFactory::new()->make( ['name' => 'Custom Name'] );

        $this->assertEquals( 'Custom Name', $user->name );
    }

    public function test_it_can_use_sequences() {
        $users = UserFactory::new()->count( 2 )->state(
            new Sequence(
                ['role' => 'editor'],
                ['role' => 'subscriber']
            ) 
        )->make();

        $this->assertEquals( 'editor', $users[0]->role );
        $this->assertEquals( 'subscriber', $users[1]->role );
    }

    public function test_it_can_generate_raw_attributes() {
        $attributes = UserFactory::new()->raw();

        $this->assertIsArray( $attributes );
        $this->assertArrayHasKey( 'name', $attributes );
        $this->assertArrayHasKey( 'email', $attributes );
    }

    public function test_it_can_run_after_making_hooks() {
        $check = false;
        UserFactory::new()->after_making(
            function() use ( &$check ) {
                $check = true;
            } 
        )->make();

        $this->assertTrue( $check );
    }

    public function test_magic_states_proxy_to_state() {
        // Calling a non-existent method that isn't in the class 
        // should trigger __call and treat it as a state override for boolean flags
        $user = UserFactory::new()->active()->make();

        $this->assertTrue( $user->active );
    }

    public function test_it_calls_configure_hook() {
        $factory = new class extends UserFactory {
            public $configured = false;

            public function configure() {
                $this->configured = true;
                return $this;
            }
        };

        $instance = $factory::new();
        $this->assertTrue( $instance->configured );
    }

    public function test_it_propagates_recycled_models_to_nested_factories() {
        $user         = new User();
        $user->id     = 123; // Use 'id' instead of 'ID' to match Model::$primary_key
        $user->exists = true;

        // Create a PostFactory that uses UserFactory for post_author
        $post_factory = new class extends Factory {
            protected $model = \WpMVC\Database\Tests\Framework\Mocks\Post::class;

            public function definition(): array {
                return ['post_author' => \WpMVC\Database\Tests\Unit\Eloquent\UserFactory::new()];
            }
        };

        // If we recycle the user, the nested UserFactory should use it
        $post = $post_factory->recycle( $user )->make();

        $this->assertEquals( 123, $post->post_author );
    }
}

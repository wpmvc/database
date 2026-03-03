<?php

namespace WpMVC\Database\Tests\Integration\WordPress;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Integration\WordPress\Models\User;
use WpMVC\Database\Tests\Integration\WordPress\Models\UserMeta;

class UserTest extends TestCase {
    public function test_it_can_create_a_user() {
        $user = User::create(
            [
                'user_login'      => 'testuser',
                'user_pass'       => 'password',
                'user_email'      => 'test@example.com',
                'user_registered' => '2026-01-01 10:00:00',
                'display_name'    => 'Test User',
            ]
        );

        $this->assertIsInt( $user->ID );
        $this->assertEquals( 'testuser', $user->user_login );
        $this->assertInstanceOf( 'DateTime', $user->user_registered );
        $this->assertEquals( '2026-01-01 10:00:00', $user->user_registered->format( 'Y-m-d H:i:s' ) );
    }

    public function test_it_can_handle_usermeta() {
        $user = User::create(
            [
                'user_login' => 'metauser',
                'user_email' => 'meta@example.com',
            ]
        );

        $user->meta()->create(
            [
                'meta_key'   => 'first_name',
                'meta_value' => 'Test',
            ]
        );

        $this->assertCount( 1, $user->meta );
        $this->assertEquals( 'first_name', $user->meta->first()->meta_key );
        $this->assertEquals( 'Test', $user->meta->first()->meta_value );

        // Test relationship back to user
        $meta = $user->meta->first();
        $this->assertEquals( $user->ID, $meta->user->ID );
    }

    public function test_it_casts_user_status_to_int() {
        $user = User::create(
            [
                'user_login'  => 'statususer',
                'user_status' => '1',
            ]
        );

        $this->assertIsInt( $user->user_status );
        $this->assertEquals( 1, $user->user_status );
    }
}

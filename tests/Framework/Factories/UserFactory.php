<?php

namespace WpMVC\Database\Tests\Framework\Factories;

use WpMVC\Database\Eloquent\Factory;
use WpMVC\Database\Tests\Framework\Mocks\User;

class UserFactory extends Factory {
    protected $model = User::class;

    public function definition(): array {
        return [
            'user_login' => $this->faker()->user_name(),
            'name'       => $this->faker()->name(),
            'email'      => $this->faker()->safe_email(),
            'role'       => 'user',
            'active'     => true,
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

<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestUser extends Model {
    protected string $table = 'test_users';

    protected array $fillable = ['id', 'name', 'email'];

    protected array $casts = [
        'meta'      => 'json',
        'is_active' => 'boolean',
    ];

    public static function get_table_name(): string {
        return 'test_users';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function posts() {
        return $this->has_many( TestPost::class, 'user_id' );
    }

    public function profile() {
        return $this->has_one( TestProfile::class, 'user_id' );
    }

    public function roles() {
        return $this->belongs_to_many( TestRole::class, 'test_user_roles', 'user_id', 'role_id' );
    }

    public function image() {
        return $this->morph_one( TestImage::class, 'imageable' );
    }

    public function images() {
        return $this->morph_many( TestImage::class, 'imageable' );
    }

    public function latest_post() {
        return $this->has_one_of_many( TestPost::class, 'user_id', 'id', 'id', 'desc' );
    }

    public function oldest_post() {
        return $this->has_one_of_many( TestPost::class, 'user_id', 'id', 'id', 'asc' );
    }
}

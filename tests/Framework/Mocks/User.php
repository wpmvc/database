<?php

namespace WpMVC\Database\Tests\Framework\Mocks;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Concerns\HasFactory;
use WpMVC\Database\Resolver;

class User extends Model {
    use HasFactory;

    public static function get_table_name(): string {
        return 'mock_users'; }

    public function resolver(): Resolver {
        return new MockResolver(); }

    public function posts() {
        return $this->has_many( Post::class, 'post_author' ); }
}

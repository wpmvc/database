<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestNetworkUser extends Model {
    protected string $table = 'users';

    public static function get_table_name(): string {
        return 'users';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function posts() {
        return $this->has_many( TestPost::class, 'user_id' );
    }
}

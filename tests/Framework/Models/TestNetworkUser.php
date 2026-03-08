<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;

class TestNetworkUser extends Model {
    protected string $table = 'users';

    public static function get_table_name(): string {
        return 'users';
    }

    public function posts() {
        return $this->has_many( TestPost::class, 'user_id' );
    }
}

<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;

class TestRole extends Model {
    protected string $table = 'test_roles';

    protected array $fillable = ['id', 'name'];

    public static function get_table_name(): string {
        return 'test_roles';
    }

    public function users() {
        return $this->belongs_to_many( TestUser::class, 'test_user_roles', 'role_id', 'user_id' );
    }
}

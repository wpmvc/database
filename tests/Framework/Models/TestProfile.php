<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestProfile extends Model {
    protected string $table = 'test_profiles';

    protected array $fillable = ['id', 'bio', 'user_id'];

    public static function get_table_name(): string {
        return 'test_profiles';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function user() {
        return $this->belongs_to( TestUser::class, 'user_id' );
    }
}

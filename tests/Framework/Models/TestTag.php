<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestTag extends Model {
    protected array $fillable = ['id', 'name'];

    public static function get_table_name(): string {
        return 'test_tags';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function posts() {
        return $this->morphed_by_many( TestPost::class, 'taggable', 'test_taggables' );
    }
}

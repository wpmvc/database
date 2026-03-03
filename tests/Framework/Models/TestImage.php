<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestImage extends Model {
    protected array $fillable = ['id', 'url', 'imageable_id', 'imageable_type'];

    public static function get_table_name(): string {
        return 'test_images';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function imageable() {
        return $this->morph_to( 'imageable' );
    }
}

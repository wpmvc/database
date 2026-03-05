<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;

class TestImage extends Model {
    protected array $fillable = ['id', 'url', 'imageable_id', 'imageable_type'];

    public static function get_table_name(): string {
        return 'test_images';
    }

    public function imageable() {
        return $this->morph_to( 'imageable' );
    }
}

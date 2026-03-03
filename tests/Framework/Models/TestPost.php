<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestPost extends Model {
    protected string $table = 'test_posts';

    protected array $fillable = ['id', 'title', 'user_id', 'status'];

    public static function get_table_name(): string {
        return 'test_posts';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function user() {
        return $this->belongs_to( TestUser::class, 'user_id' );
    }

    public function network_user() {
        return $this->belongs_to( TestNetworkUser::class, 'user_id' );
    }

    public function image() {
        return $this->morph_one( TestImage::class, 'imageable' );
    }

    public function tags() {
        return $this->morph_to_many( TestTag::class, 'taggable', 'test_taggables' );
    }
}

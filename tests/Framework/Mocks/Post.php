<?php

namespace WpMVC\Database\Tests\Framework\Mocks;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Concerns\HasFactory;
use WpMVC\Database\Resolver;

class Post extends Model {
    use HasFactory;

    public static function get_table_name(): string {
        return 'mock_posts'; }

    public function resolver(): Resolver {
        return new MockResolver(); }

    public function user() {
        return $this->belongs_to( User::class, 'post_author' ); }

    public function comments() {
        return $this->morph_many( Comment::class, 'commentable' ); }
}

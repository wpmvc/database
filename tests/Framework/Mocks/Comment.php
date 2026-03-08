<?php

namespace WpMVC\Database\Tests\Framework\Mocks;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Concerns\HasFactory;
use WpMVC\Database\Resolver;

class Comment extends Model {
    use HasFactory;

    public static function get_table_name(): string {
        return 'mock_comments'; }

    public function resolver(): Resolver {
        return new MockResolver(); }

    public function commentable() {
        return $this->morph_to( 'commentable' ); }
}

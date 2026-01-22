<?php

namespace WpMVC\Database\Tests\Fixtures\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class Profile extends Model {
    public static function get_table_name(): string {
        return 'profiles';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }
}

<?php

namespace WpMVC\Database\Tests\Framework\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class TestCountry extends Model {
    public static function get_table_name(): string {
        return 'test_countries';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }

    public function posts() {
        return $this->has_many_through( TestPost::class, TestUser::class, 'country_id', 'user_id' );
    }
    
    public function users() {
        return $this->has_many( TestUser::class, 'country_id' );
    }

    public function profile() {
        return $this->has_one_through( TestProfile::class, TestUser::class, 'country_id', 'user_id' );
    }
}

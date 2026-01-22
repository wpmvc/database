<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasMany;
use WpMVC\Database\Resolver;
use WpMVC\Database\Tests\TestCase;

class TestModel extends Model {
    public static function get_table_name(): string {
        return 'test_posts';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }
}

class TestRelatedModel extends Model {
    public static function get_table_name(): string {
        return 'test_comments';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }
}

class ModelTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that a model can initiate a new query builder instance.
     */
    public function it_can_initiate_query() {
        $query = TestModel::query();
        
        $this->assertInstanceOf( 'WpMVC\Database\Query\Builder', $query );
        $this->assertEquals( 'select * from wp_test_posts as test_posts', $query->to_sql() );
    }

    /**
     * @test
     * 
     * Verifies that a model can define a has_many relationship.
     */
    public function it_can_define_has_many_relationship() {
        $model = new TestModel();
        
        $relation = $model->has_many( TestRelatedModel::class, 'post_id', 'id' );
        
        $this->assertInstanceOf( HasMany::class, $relation );
        $this->assertInstanceOf( TestRelatedModel::class, $relation->get_related() );
    }
    
    // Additional basic model tests can go here
}

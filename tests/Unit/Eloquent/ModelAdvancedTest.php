<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use Mockery;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;
use WpMVC\Database\Tests\TestCase;

class AdvancedModel extends Model {
    public static function get_table_name(): string {
        return 'advanced_items';
    }

    public function resolver(): Resolver {
        return new Resolver();
    }
}

class ModelAdvancedTest extends TestCase {
    /**
     * @test
     * 
     * Verifies that the model can start a query with a custom alias.
     */
    public function it_can_query_with_alias() {
        $query = AdvancedModel::query( 'ai' );
        
        $this->assertEquals( 'select * from wp_advanced_items as ai', $query->to_sql() );
    }

    /**
     * @test
     * 
     * Verifies that the model provides a valid resolver instance.
     */
    public function it_shares_resolver_instance() {
        $model = new AdvancedModel();
        
        $this->assertInstanceOf( Resolver::class, $model->resolver() );
    }
}

<?php

namespace WpMVC\Database\Tests\Integration\Clauses;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Schema\Schema;
use WpMVC\Database\Schema\Blueprint;
use WpMVC\Database\Tests\Framework\Models\TestUser;

/**
 * Integration tests for HAVING, GROUP BY, and ON clause behaviour.
 */
class ClauseIntegrationTest extends TestCase {
    public function set_up(): void {
        parent::set_up();

        Schema::drop_if_exists( 'clause_orders' );
        Schema::drop_if_exists( 'clause_products' );

        Schema::create(
            'clause_products', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->string( 'category' );
                $table->string( 'name' );
                $table->integer( 'price' );
            }
        );

        Schema::create(
            'clause_orders', function( Blueprint $table ) {
                $table->big_increments( 'id' );
                $table->unsigned_big_integer( 'product_id' );
                $table->integer( 'quantity' );
            }
        );

        // Seed
        global $wpdb;
        $p = $wpdb->prefix . 'clause_products';
        $wpdb->query( 
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "INSERT INTO `$p` (category, name, price) VALUES
            ('Electronics', 'Phone', 500),
            ('Electronics', 'Tablet', 800),
            ('Books', 'Novel', 20),
            ('Books', 'Textbook', 60),
            ('Books', 'Dictionary', 40)
        " 
        );

        $o = $wpdb->prefix . 'clause_orders';
        $wpdb->query(
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "INSERT INTO `$o` (product_id, quantity) VALUES
            (1, 3), (1, 2), (2, 1), (3, 10), (4, 5), (5, 2)
        " 
        );
    }

    public function tear_down(): void {
        Schema::drop_if_exists( 'clause_orders' );
        Schema::drop_if_exists( 'clause_products' );
        parent::tear_down();
    }

    protected function product_builder(): Builder {
        $b = new Builder( new TestUser() );
        $b->from( 'clause_products' );
        return $b;
    }

    protected function order_builder(): Builder {
        $b = new Builder( new TestUser() );
        $b->from( 'clause_orders' );
        return $b;
    }

    // =========================================================================
    // GROUP BY
    // =========================================================================

    public function test_group_by_category_returns_one_row_per_category() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->group_by( 'category' )
            ->get();

        $this->assertCount( 2, $results );
        $categories = array_map( fn( $r ) => $r->category, $results->all() );
        sort( $categories );
        $this->assertEquals( ['Books', 'Electronics'], $categories );
    }

    public function test_group_by_with_count() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->add_select( ['cnt' => 'COUNT(*)'] )
            ->group_by( 'category' )
            ->order_by( 'cnt', 'desc' )
            ->get();

        $this->assertCount( 2, $results );
        // Books has 3, Electronics has 2
        $this->assertEquals( 'Books', $results[0]->category );
        $this->assertEquals( 3, (int) $results[0]->cnt );
    }

    public function test_group_by_with_sum() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->add_select( ['total_price' => 'SUM(price)'] )
            ->group_by( 'category' )
            ->order_by( 'total_price', 'desc' )
            ->get();

        $this->assertCount( 2, $results );
        // Electronics: 500+800=1300, Books: 20+60+40=120
        $this->assertEquals( 'Electronics', $results[0]->category );
        $this->assertEquals( 1300, (int) $results[0]->total_price );
        $this->assertEquals( 120, (int) $results[1]->total_price );
    }

    // =========================================================================
    // HAVING
    // =========================================================================

    public function test_having_filters_groups_by_aggregate() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->add_select( ['cnt' => 'COUNT(*)'] )
            ->group_by( 'category' )
            ->having( 'cnt', '>', 2 )
            ->get();

        // Only Books has 3 items > 2
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Books', $results[0]->category );
    }

    public function test_having_with_sum_threshold() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->add_select( ['total_price' => 'SUM(price)'] )
            ->group_by( 'category' )
            ->having( 'total_price', '>=', 1000 )
            ->get();

        $this->assertCount( 1, $results );
        $this->assertEquals( 'Electronics', $results[0]->category );
    }

    public function test_having_raw() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->add_select( ['avg_price' => 'AVG(price)'] )
            ->group_by( 'category' )
            ->having_raw( 'AVG(price) > 100' )
            ->get();

        // Electronics avg = (500+800)/2 = 650, Books avg = (20+60+40)/3 ≈ 40
        $this->assertCount( 1, $results );
        $this->assertEquals( 'Electronics', $results[0]->category );
    }

    public function test_or_having_includes_additional_groups() {
        $results = $this->product_builder()
            ->select( 'category' )
            ->add_select( ['cnt' => 'COUNT(*)'] )
            ->add_select( ['total' => 'SUM(price)'] )
            ->group_by( 'category' )
            ->having( 'cnt', '>', 2 )
            ->or_having( 'total', '>', 1000 )
            ->get();

        // Books: cnt=3 (>2) ✓, Electronics: total=1300 (>1000) ✓
        $this->assertCount( 2, $results );
    }

    // =========================================================================
    // ON clause (JOIN + ON)
    // =========================================================================

    public function test_join_with_on_clause_returns_matching_rows() {
        $results = $this->product_builder()
            ->select( 'clause_products.name', 'clause_orders.quantity' )
            ->join( 'clause_orders', 'clause_products.id', '=', 'clause_orders.product_id' )
            ->order_by( 'clause_products.id' )
            ->get();

        // Phone has 2 orders, Tablet 1, Novel 1, Textbook 1, Dictionary 1 = 6 rows
        $this->assertCount( 6, $results );
        $this->assertEquals( 'Phone', $results[0]->name );
    }

    public function test_left_join_with_on_clause_includes_unmatched_rows() {
        // No order for Textbook with quantity > 10 — left join should still return product
        $results = $this->product_builder()
            ->select( 'clause_products.name' )
            ->add_select( ['total_qty' => 'SUM(clause_orders.quantity)'] )
            ->left_join( 'clause_orders', 'clause_products.id', '=', 'clause_orders.product_id' )
            ->group_by( 'clause_products.id', 'clause_products.name' )
            ->order_by( 'clause_products.id' )
            ->get();

        // All 5 products should appear
        $this->assertCount( 5, $results );
    }

    public function test_join_with_callback_on_clause() {
        $results = $this->product_builder()
            ->select( 'clause_products.name', 'clause_orders.quantity' )
            ->join(
                'clause_orders', function( $join ) {
                    $join->on( 'clause_products.id', '=', 'clause_orders.product_id' )
                         ->where( 'clause_orders.quantity', '>', 2 );
                }
            )
            ->get();

        // Phone: qty 3 ✓, qty 2 ✗ | Novel: qty 10 ✓ | Textbook: qty 5 ✓ = 3 rows
        $this->assertCount( 3, $results );
    }

    // =========================================================================
    // Combined GROUP BY + HAVING + JOIN
    // =========================================================================

    public function test_group_by_having_with_join() {
        $results = $this->product_builder()
            ->select( 'clause_products.category' )
            ->add_select( ['total_qty' => 'SUM(clause_orders.quantity)'] )
            ->join( 'clause_orders', 'clause_products.id', '=', 'clause_orders.product_id' )
            ->group_by( 'clause_products.category' )
            ->having( 'total_qty', '>', 5 )
            ->get();

        // Electronics: 3+2+1=6 ✓, Books: 10+5+2=17 ✓
        $this->assertCount( 2, $results );
    }
}

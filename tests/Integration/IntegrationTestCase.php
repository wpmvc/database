<?php

namespace WpMVC\Database\Tests\Integration;

use WpMVC\Database\Tests\TestCase as BaseTestCase;

/**
 * Base class for integration tests.
 * 
 * Integration tests verify that multiple components work together correctly
 * with actual database interactions. Uses enhanced wpdb mock for realistic testing.
 */
abstract class IntegrationTestCase extends BaseTestCase {
    /**
     * Setup test database state before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setupTestDatabase();
    }

    /**
     * Setup mock wpdb with test data.
     */
    protected function setupTestDatabase(): void {
        global $wpdb;
        
        // Reset wpdb mock state
        $wpdb->queries    = [];
        $wpdb->last_query = '';
        $wpdb->insert_id  = 0;
        $wpdb->num_rows   = 0;
    }

    /**
     * Mock wpdb to return specific results for testing.
     *
     * @param array $results
     * @return void
     */
    protected function mockDatabaseResults( array $results ): void {
        global $wpdb;
        $wpdb->mock_results = $results;
        $wpdb->num_rows     = count( $results );
    }

    /**
     * Get the last executed SQL query.
     *
     * @return string
     */
    protected function getLastQuery(): string {
        global $wpdb;
        return $wpdb->last_query ?? '';
    }

    /**
     * Assert that a query was executed.
     *
     * @param string $expectedSQL
     * @return void
     */
    protected function assertQueryExecuted( string $expected_sql ): void {
        $last_query = $this->getLastQuery();
        $this->assert_sql_equals( $expected_sql, $last_query, 'Expected query was not executed' );
    }
}

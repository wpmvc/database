<?php

namespace WpMVC\Database\Tests\Assertions;

/**
 * Trait providing custom SQL assertion methods for better test maintainability.
 */
trait SQLAssertions {
    /**
     * Assert that two SQL strings are equal after normalization.
     * Normalizes whitespace to make tests less brittle.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     * @return void
     */
    protected function assert_sql_equals( string $expected, string $actual, string $message = '' ): void {
        $this->assertEquals(
            $this->normalize_sql( $expected ),
            $this->normalize_sql( $actual ),
            $message
        );
    }

    /**
     * Assert SQL contains a specific clause after normalization.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     * @return void
     */
    protected function assert_sql_contains( string $needle, string $haystack, string $message = '' ): void {
        $this->assertStringContainsString(
            $this->normalize_sql( $needle ),
            $this->normalize_sql( $haystack ),
            $message
        );
    }

    /**
     * Normalize SQL string by removing extra whitespace and converting to lowercase.
     * This makes SQL comparison more flexible and less brittle.
     *
     * @param string $sql
     * @return string
     */
    protected function normalize_sql( string $sql ): string {
        // Replace multiple whitespace with single space
        $normalized = preg_replace( '/\s+/', ' ', $sql );
        
        // Trim and convert to lowercase for case-insensitive comparison
        return trim( strtolower( $normalized ) );
    }

    /**
     * Assert SQL matches a regular expression pattern.
     *
     * @param string $pattern
     * @param string $sql
     * @param string $message
     * @return void
     */
    protected function assert_sql_matches( string $pattern, string $sql, string $message = '' ): void {
        $this->assertMatchesRegularExpression(
            $pattern,
            $this->normalize_sql( $sql ),
            $message
        );
    }
}

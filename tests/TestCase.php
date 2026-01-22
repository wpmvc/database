<?php

namespace WpMVC\Database\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;
use WpMVC\Database\Tests\Assertions\SQLAssertions;

/**
 * Base test case for all database tests.
 * Provides Mockery integration and SQL assertion helpers.
 */
abstract class TestCase extends BaseTestCase {
    use SQLAssertions;

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }
}

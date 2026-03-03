<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Seeder;

class MockSeeder extends Seeder {
    public static $run_count = 0;

    public function run() {
        static::$run_count++;
    }
}

class ParentSeeder extends Seeder {
    public function run() {
        $this->call( MockSeeder::class );
    }
}

class SeederTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        MockSeeder::$run_count = 0;
    }

    public function test_it_can_run_a_seeder() {
        $seeder = new MockSeeder();
        $seeder->run();

        $this->assertEquals( 1, MockSeeder::$run_count );
    }

    public function test_it_can_call_other_seeders() {
        $seeder = new ParentSeeder();
        $seeder->run();

        $this->assertEquals( 1, MockSeeder::$run_count );
    }

    public function test_it_can_run_programmatically() {
        Seeder::run_seeder( MockSeeder::class );

        $this->assertEquals( 1, MockSeeder::$run_count );
    }
}

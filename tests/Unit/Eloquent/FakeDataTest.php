<?php

namespace WpMVC\Database\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use WpMVC\Database\Eloquent\FakeData;

class FakeDataTest extends TestCase {
    protected FakeData $faker;

    protected function setUp(): void {
        parent::setUp();
        $this->faker = FakeData::instance();
    }

    public function test_it_can_generate_names() {
        $this->assertIsString( $this->faker->name() );
        $this->assertIsString( $this->faker->first_name() );
        $this->assertIsString( $this->faker->last_name() );
    }

    public function test_it_can_generate_text() {
        $this->assertIsString( $this->faker->word() );
        $this->assertIsString( $this->faker->sentence() );
        $this->assertIsString( $this->faker->paragraph() );
        $this->assertIsString( $this->faker->text() );
        $this->assertIsString( $this->faker->slug() );
    }

    public function test_it_can_generate_internet_data() {
        $this->assertStringContainsString( '@', $this->faker->email() );
        $this->assertStringContainsString( '@', $this->faker->safe_email() );
        $this->assertIsString( $this->faker->user_name() );
        $this->assertIsString( $this->faker->domain_name() );
        $this->assertStringStartsWith( 'https://', $this->faker->url() );
    }

    public function test_it_can_generate_numbers() {
        $number = $this->faker->number_between( 1, 10 );
        $this->assertGreaterThanOrEqual( 1, $number );
        $this->assertLessThanOrEqual( 10, $number );

        $this->assertIsInt( $this->faker->random_digit() );
        $this->assertIsFloat( $this->faker->random_float() );
    }

    public function test_it_can_generate_dates() {
        $this->assertIsString( $this->faker->date() );
        $this->assertIsString( $this->faker->time() );
        $this->assertIsString( $this->faker->date_time() );
        $this->assertIsString( $this->faker->iso8601() );
        $this->assertIsInt( $this->faker->timestamp() );
    }

    public function test_it_can_generate_locations() {
        $this->assertIsString( $this->faker->address() );
        $this->assertIsString( $this->faker->city() );
        $this->assertIsString( $this->faker->street_name() );
        $this->assertIsString( $this->faker->postcode() );
        $this->assertIsString( $this->faker->country() );
    }

    public function test_it_can_generate_uuids() {
        $uuid = $this->faker->uuid();
        $this->assertMatchesRegularExpression( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid );
    }

    public function test_it_can_generate_booleans() {
        $this->assertIsBool( $this->faker->boolean() );
    }

    public function test_it_can_select_random_elements() {
        $array   = ['a', 'b', 'c'];
        $element = $this->faker->random_element( $array );
        $this->assertContains( $element, $array );
    }
}

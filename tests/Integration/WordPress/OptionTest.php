<?php
/**
 * Option Integration Test
 *
 * @package WpMVC\Database\Tests\Integration\WordPress
 */

namespace WpMVC\Database\Tests\Integration\WordPress;

use WpMVC\Database\Tests\Framework\TestCase;
use WpMVC\Database\Tests\Integration\WordPress\Models\Option;

/**
 * Class OptionTest
 */
class OptionTest extends TestCase {
    /**
     * Test creating an option.
     */
    public function test_it_can_create_an_option() {
        $option = Option::create(
            [
                'option_name'  => 'test_option',
                'option_value' => 'test_value',
                'autoload'     => 'yes',
            ]
        );

        $this->assertIsInt( $option->option_id );
        $this->assertEquals( 'test_option', $option->option_name );
        $this->assertEquals( 'test_value', $option->option_value );
    }

    /**
     * Test fetching an option.
     */
    public function test_it_can_fetch_an_option() {
        Option::create(
            [
                'option_name'  => 'fetch_me',
                'option_value' => 'secret',
            ]
        );

        $option = Option::where( 'option_name', 'fetch_me' )->first();
        $this->assertEquals( 'secret', $option->option_value );
    }

    /**
     * Test updating an option.
     */
    public function test_it_can_update_an_option() {
        $option = Option::create(
            [
                'option_name'  => 'updatable',
                'option_value' => 'old',
            ]
        );

        $option->option_value = 'new';
        $option->save();

        $this->assertEquals( 'new', Option::find( $option->option_id )->option_value );
    }
}

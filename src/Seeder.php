<?php
/**
 * Base Seeder class for WpMVC.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Class Seeder
 *
 * The base class for all database seeders.
 */
abstract class Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Seed the given connection from the given path.
     *
     * @param  array|string  $class
     * @return $this
     */
    public function call( $class ) {
        $classes = (array) $class;

        foreach ( $classes as $class ) {
            $this->resolve( $class )->run();
        }

        return $this;
    }

    /**
     * Run a seeder programmatically.
     *
     * @param  string  $class
     * @return void
     */
    public static function run_seeder( string $class ) {
        ( new $class() )->run();
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string  $class
     * @return Seeder
     */
    protected function resolve( $class ) {
        if ( ! class_exists( $class ) ) {
            $namespace = __NAMESPACE__;
            $parts     = explode( '\\', $namespace );
            $namespace = $parts[0];

            // Check in convention namespace
            $namespaced_class = "{$namespace}\\Database\\Seeders\\{$class}";
            if ( class_exists( $namespaced_class ) ) {
                $class = $namespaced_class;
            }
        }

        if ( ! class_exists( $class ) ) {
            throw new InvalidArgumentException( "Seeder class [{$class}] not found." );
        }

        return new $class();
    }
}

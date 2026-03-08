<?php
/**
 * HasFactory trait for WpMVC models.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Concerns;

defined( 'ABSPATH' ) || exit;

use WpMVC\Database\Eloquent\Factory;

/**
 * Trait HasFactory
 *
 * Provides factory support for Eloquent models.
 */
trait HasFactory {
    /**
     * Create a new factory instance for the model.
     *
     * @param  int|null  $count
     * @return Factory
     */
    public static function factory( ?int $count = null ) {
        $factory = static::new_factory() ?: Factory::new_factory_for_model( static::class );

        return $factory->count( $count );
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory|null
     */
    protected static function new_factory() {
        return null;
    }
}

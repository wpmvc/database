<?php
/**
 * Model event handling trait.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Concerns;

defined( "ABSPATH" ) || exit;

use WpMVC\App;

/**
 * Trait HasEvents
 *
 * Provides methods for managing model events.
 *
 * @package WpMVC\Database\Eloquent\Concerns
 */
trait HasEvents {
    /**
     * Create a new model instance from existing database record.
     *
     * @param array $attributes
     * @return static
     */
    public function new_from_builder( array $attributes = [] ) {
        $model = new static;

        $model->attributes = (array) $attributes;

        $model->exists = true;

        $model->sync_original();

        $model->fire_model_event( 'retrieved', false );

        return $model;
    }

    /**
     * Fire a custom model event.
     *
     * @param  string  $event
     * @param  bool    $halt
     * @return mixed
     */
    protected function fire_model_event( $event, $halt = true ) {
        // Fire WordPress Hooks
        $prefix     = App::get_config()->get( 'app.hook_prefix' ) ?: 'wpmvc';
        $table_name = static::get_table_name();

        $hook_name       = "{$prefix}_model_{$event}";
        $class_hook_name = "{$hook_name}_{$table_name}";

        if ( $halt ) {
            // "ing" events (saving, creating, etc.) use filters to allow halting
            if ( false === apply_filters( $class_hook_name, true, $this ) ) {
                return false;
            }
            if ( false === apply_filters( $hook_name, true, $this ) ) {
                return false;
            }
        } else {
            // Completed events use actions
            do_action( $class_hook_name, $this );
            do_action( $hook_name, $this );
        }

        return true;
    }
}

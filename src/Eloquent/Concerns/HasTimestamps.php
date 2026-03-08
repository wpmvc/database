<?php
/**
 * Model timestamp handling trait.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Concerns;

defined( "ABSPATH" ) || exit;

/**
 * Trait HasTimestamps
 *
 * Provides methods for automatically managing model timestamps.
 *
 * @package WpMVC\Database\Eloquent\Concerns
 */
trait HasTimestamps {
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public bool $timestamps = true;

    /**
     * Update the model's timestamps.
     *
     * @return void
     */
    public function update_timestamps() {
        $time = gmdate( 'Y-m-d H:i:s' );

        if ( ! $this->exists && ! $this->is_dirty( 'created_at' ) ) {
            $this->set_attribute( 'created_at', $time );
        }

        if ( ! $this->is_dirty( 'updated_at' ) ) {
            $this->set_attribute( 'updated_at', $time );
        }
    }
}

<?php
/**
 * Model attribute guarding trait.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Concerns;

defined( "ABSPATH" ) || exit;

/**
 * Trait GuardsAttributes
 *
 * Provides methods for managing mass assignment protection.
 *
 * @package WpMVC\Database\Eloquent\Concerns
 */
trait GuardsAttributes {
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected array $guarded = ['*'];

    /**
     * Indicates if all mass assignment is unguarded.
     *
     * @var bool
     */
    protected static bool $unguarded = false;

    /**
     * Set the model to be unguarded.
     *
     * @param  bool  $state
     * @return void
     */
    public static function unguard( bool $state = true ) {
        static::$unguarded = $state;
    }

    /**
     * Set the model to be guarded.
     *
     * @return void
     */
    public static function reguard() {
        static::$unguarded = false;
    }

    /**
     * Determine if the model is currently unguarded.
     *
     * @return bool
     */
    public static function is_unguarded() {
        return static::$unguarded;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function fill( array $attributes ) {
        $totally_guarded = $this->totally_guarded();

        foreach ( $attributes as $key => $value ) {
            if ( $this->is_fillable( $key ) ) {
                $this->set_attribute( $key, $value );
            } elseif ( $totally_guarded || static::$unguarded ) {
                // If it's totally guarded and not fillable, we just ignore it for security
                // UNLESS it's globally unguarded
                if ( static::$unguarded ) {
                    $this->set_attribute( $key, $value );
                }
            }
        }

        return $this;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param  string  $key
     * @return bool
     */
    public function is_fillable( $key ) {
        if ( static::$unguarded ) {
            return true;
        }

        if ( in_array( $key, $this->fillable ) ) {
            return true;
        }

        if ( count( $this->fillable ) > 0 ) {
            return false;
        }

        if ( $this->totally_guarded() ) {
            return false;
        }

        return ! in_array( $key, $this->guarded ) && strpos( $key, '_' ) !== 0;
    }

    /**
     * Determine if the model is "totally guarded".
     *
     * @return bool
     */
    public function totally_guarded() {
        return count( $this->fillable ) === 0 && $this->guarded === ['*'];
    }

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function get_fillable() {
        return $this->fillable;
    }

    /**
     * Set the fillable attributes for the model.
     *
     * @param  array  $fillable
     * @return $this
     */
    public function fillable( array $fillable ) {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Get the guarded attributes for the model.
     *
     * @return array
     */
    public function get_guarded() {
        return $this->guarded;
    }

    /**
     * Set the guarded attributes for the model.
     *
     * @param  array  $guarded
     * @return $this
     */
    public function guarded( array $guarded ) {
        $this->guarded = $guarded;

        return $this;
    }
}

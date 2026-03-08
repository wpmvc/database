<?php
/**
 * Signup Model for Testing
 *
 * @package WpMVC\Database\Tests\Integration\WordPress\Models
 */

namespace WpMVC\Database\Tests\Integration\WordPress\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

/**
 * Class Signup
 *
 * Represents the WordPress signups table.
 */
class Signup extends Model {
    /**
     * Indicates if the model should be soft deleted.
     *
     * @var bool
     */
    public bool $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected string $primary_key = 'signup_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'domain',
        'path',
        'title',
        'user_login',
        'user_email',
        'registered',
        'activated',
        'active',
        'activation_key',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'signup_id'  => 'int',
        'registered' => 'datetime',
        'activated'  => 'datetime',
        'active'     => 'bool',
        'meta'       => 'json',
    ];

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public static function get_table_name(): string {
        return 'signups';
    }

    /**
     * Get the resolver instance.
     *
     * @return Resolver
     */
    public function resolver(): Resolver {
        return new Resolver();
    }
}

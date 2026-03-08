<?php
/**
 * User Model for Testing
 *
 * @package WpMVC\Database\Tests\Integration\WordPress\Models
 */

namespace WpMVC\Database\Tests\Integration\WordPress\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

/**
 * Class User
 *
 * Represents the WordPress users table.
 */
class User extends Model {
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
    protected string $primary_key = 'ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'user_login',
        'user_pass',
        'user_nicename',
        'user_email',
        'user_url',
        'user_registered',
        'user_activation_key',
        'user_status',
        'display_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'ID'              => 'int',
        'user_registered' => 'datetime',
        'user_status'     => 'int',
    ];

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public static function get_table_name(): string {
        return 'users';
    }

    /**
     * Get the resolver instance.
     *
     * @return Resolver
     */
    public function resolver(): Resolver {
        return new Resolver();
    }

    /**
     * Get the user's meta data.
     */
    public function meta() {
        return $this->has_many( UserMeta::class, 'user_id', 'ID' );
    }

    /**
     * Get the user's posts.
     */
    public function posts() {
        return $this->has_many( Post::class, 'post_author', 'ID' );
    }

    /**
     * Get the user's comments.
     */
    public function comments() {
        return $this->has_many( Comment::class, 'user_id', 'ID' );
    }
}

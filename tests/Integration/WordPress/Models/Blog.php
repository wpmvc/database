<?php
/**
 * Blog Model for Testing
 *
 * @package WpMVC\Database\Tests\Integration\WordPress\Models
 */

namespace WpMVC\Database\Tests\Integration\WordPress\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

/**
 * Class Blog
 *
 * Represents the WordPress blogs table.
 */
class Blog extends Model {
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
    protected string $primary_key = 'blog_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'site_id',
        'domain',
        'path',
        'registered',
        'last_updated',
        'public',
        'archived',
        'mature',
        'spam',
        'deleted',
        'lang_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'blog_id'      => 'int',
        'site_id'      => 'int',
        'registered'   => 'datetime',
        'last_updated' => 'datetime',
        'public'       => 'bool',
        'archived'     => 'bool',
        'mature'       => 'bool',
        'spam'         => 'bool',
        'deleted'      => 'bool',
        'lang_id'      => 'int',
    ];

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public static function get_table_name(): string {
        return 'blogs';
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
     * Get the site that owns the blog.
     */
    public function site() {
        return $this->belongs_to( Site::class, 'site_id', 'id' );
    }

    /**
     * Get the blog's meta data.
     */
    public function meta() {
        return $this->has_many( BlogMeta::class, 'blog_id', 'blog_id' );
    }
}

<?php
/**
 * Comment Model for Testing
 *
 * @package WpMVC\Database\Tests\Integration\WordPress\Models
 */

namespace WpMVC\Database\Tests\Integration\WordPress\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

/**
 * Class Comment
 *
 * Represents the WordPress comments table.
 */
class Comment extends Model {
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
    protected string $primary_key = 'comment_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'comment_post_ID',
        'comment_author',
        'comment_author_email',
        'comment_author_url',
        'comment_author_IP',
        'comment_date',
        'comment_date_gmt',
        'comment_content',
        'comment_karma',
        'comment_approved',
        'comment_agent',
        'comment_type',
        'comment_parent',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'comment_ID'       => 'int',
        'comment_post_ID'  => 'int',
        'comment_date'     => 'datetime',
        'comment_date_gmt' => 'datetime',
        'comment_karma'    => 'int',
        'comment_parent'   => 'int',
        'user_id'          => 'int',
    ];

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public static function get_table_name(): string {
        return 'comments';
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
     * Get the post that owns the comment.
     */
    public function post() {
        return $this->belongs_to( Post::class, 'comment_post_ID' );
    }

    /**
     * Get the user that owns the comment.
     */
    public function user() {
        return $this->belongs_to( User::class, 'user_id' );
    }

    /**
     * Get the comment's meta data.
     */
    public function meta() {
        return $this->has_many( CommentMeta::class, 'comment_id' );
    }
}

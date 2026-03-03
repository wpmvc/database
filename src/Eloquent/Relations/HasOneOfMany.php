<?php
/**
 * HasOneOfMany relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Query\Builder;
use WpMVC\Database\Eloquent\Model;

/**
 * Class HasOneOfMany
 *
 * Defines a one-to-one of many relationship (e.g., "latest post").
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class HasOneOfMany extends HasOne {
    /**
     * The column to sort by.
     *
     * @var string
     */
    public string $sort_column;

    /**
     * The direction to sort by.
     *
     * @var string
     */
    public string $sort_direction;

    /**
     * Create a new has one of many relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  string   $foreign_key
     * @param  string   $local_key
     * @param  string   $sort_column
     * @param  string   $sort_direction
     */
    public function __construct( Builder $query, Model $parent, $foreign_key, $local_key, $sort_column = 'id', $sort_direction = 'desc' ) {
        $this->sort_column    = $sort_column;
        $this->sort_direction = $sort_direction;

        parent::__construct( $query, $parent, $foreign_key, $local_key );
    }

    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        parent::add_constraints();

        $this->order_by( $this->sort_column, $this->sort_direction );
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {
        parent::add_eager_constraints( $models );

        $this->order_by( $this->sort_column, $this->sort_direction );
    }
}

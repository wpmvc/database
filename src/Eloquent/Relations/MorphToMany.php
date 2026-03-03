<?php
/**
 * MorphToMany relationship class.
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
 * Class MorphToMany
 *
 * Defines a polymorphic many-to-many relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class MorphToMany extends BelongsToMany {
    /**
     * The morph name of the relationship.
     *
     * @var string
     */
    public $morph_name;

    /**
     * The morph type (class name) assigned to this relationship instance.
     *
     * @var string
     */
    public $morph_type;

    /**
     * Indicates if the relationship is an inverse relationship.
     *
     * @var bool
     */
    public $inverse;

    /**
     * The type column for the polymorphic relationship.
     *
     * @var string
     */
    public $type_column;

    /**
     * Create a new morph to many relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  string   $morph_name
     * @param  string   $morph_type
     * @param  string   $pivot_table
     * @param  string   $foreign_pivot_key
     * @param  string   $local_pivot_key
     * @param  string   $foreign_key
     * @param  string   $local_key
     * @param  bool     $inverse
     */
    public function __construct( Builder $query, Model $parent, $morph_name, $morph_type, $pivot_table, $foreign_pivot_key, $local_pivot_key, $foreign_key, $local_key, $inverse = false ) {
        $this->morph_name  = $morph_name;
        $this->morph_type  = $morph_type;
        $this->inverse     = $inverse;
        $this->type_column = $morph_name . '_type';

        parent::__construct( $query, $parent, $pivot_table, $foreign_pivot_key, $local_pivot_key, $foreign_key, $local_key );
    }

    /**
     * Perform the join for the relationship.
     *
     * @param  Builder|null  $query
     * @return void
     */
    protected function perform_join( $query = null ) {
        parent::perform_join( $query );

        $query = $query ?: $this;
        
        $pivot_table = $this->get_pivot_table();

        $query->where( "{$pivot_table}.{$this->type_column}", '=', $this->morph_type );
    }

    /**
     * Get the name of the pivot table.
     *
     * @return string
     */
    public function get_pivot_table() {
        // Since MorphToMany often uses a table name string instead of a model class for the pivot
        return $this->pivot instanceof Model ? $this->pivot::get_table_name() : $this->pivot;
    }

    /**
     * Attach a model instance to the parent model.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return void
     */
    public function attach( $id, array $attributes = [] ) {
        $attributes[$this->type_column] = $this->morph_type;

        parent::attach( $id, $attributes );
    }
}

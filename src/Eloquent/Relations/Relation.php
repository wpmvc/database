<?php
/**
 * Base Relation class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Collection;
use WpMVC\Database\Query\Builder;

/**
 * Class Relation
 *
 * The base class for all Eloquent relationships.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
abstract class Relation extends Builder {
    /**
     * The parent model instance.
     *
     * @var Model
     */
    protected Model $parent;

    /**
     * The related model instance.
     *
     * @var Model
     */
    protected Model $related;

    /**
     * The foreign key on the related model.
     *
     * @var string
     */
    public $foreign_key;

    /**
     * The local key on the parent model.
     *
     * @var string
     */
    public $local_key;

    /**
     * Create a new relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  string   $foreign_key
     * @param  string   $local_key
     */
    public function __construct( Builder $query, Model $parent, $foreign_key, $local_key ) {
        parent::__construct( $query->model );

        // Inherit state from the existing query
        $this->from     = $query->from;
        $this->as       = $query->as;
        $this->columns  = $query->columns;
        $this->joins    = $query->joins;
        $this->orders   = $query->orders;
        $this->limit    = $query->limit;
        $this->offset   = $query->offset;
        $this->distinct = $query->distinct;
        $this->set_bindings( $query->get_bindings() );

        // Copy clauses from the query traits
        $this->clauses = $query->get_clauses();

        $this->parent      = $parent;
        $this->related     = $query->model;
        $this->foreign_key = $foreign_key;
        $this->local_key   = $local_key;
    }

    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {}

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {}

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    protected function init_relation( array $models, string $relation ) {
        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array   $models
     * @param  array   $results
     * @param  string  $relation
     * @return array
     */
    protected function match( array $models, array $results, string $relation ) {
        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    abstract public function get_results();

    /**
     * Get the results of the relationship for eager loading.
     *
     * @return Collection
     */
    public function get_eager() {
        return $this->get();
    }

    /**
     * Wrap the results in a collection if necessary.
     *
     * @param  mixed  $results
     * @return array|Collection
     */
    protected function get_results_as_collection( $results ) {
        if ( ! $this->is_many() ) {
            return $results;
        }

        if ( $results instanceof Collection ) {
            return $results;
        }

        return $this->related->new_collection( (array) $results );
    }

    /**
     * Determine if the relationship is a "many" relationship.
     *
     * @return bool
     */
    protected function is_many() {
        return $this instanceof HasMany ||
               $this instanceof BelongsToMany ||
               $this instanceof MorphMany ||
               $this instanceof MorphToMany ||
               $this instanceof HasManyThrough;
    }

    /**
     * Get the underlying query for the relation.
     *
     * @return Builder
     */
    protected function get_query() {
        return $this;
    }

    /**
     * Get the related model of the relation.
     *
     * @return Model
     */
    protected function get_related() {
        return $this->related;
    }

    /**
     * Get the parent model of the relation.
     *
     * @return Model
     */
    protected function get_parent() {
        return $this->parent;
    }

    /**
     * Get the fully qualified foreign key name.
     *
     * @return string
     */
    public function get_qualified_foreign_key() {
        return $this->related::get_table_name() . '.' . $this->foreign_key;
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function get_qualified_parent_key_name() {
        return $this->parent::get_table_name() . '.' . $this->local_key;
    }

    /**
     * Get the key for comparing relationship existence.
     *
     * @return string
     */
    public function get_relation_existence_compare_key() {
        return $this->get_qualified_foreign_key();
    }

    /**
     * Get the name of the parent key.
     *
     * @return string
     */
    public function get_parent_key_name() {
        return $this->local_key;
    }

    /**
     * Get the existence query for a with_count aggregate.
     *
     * @param  Builder  $parent_query
     * @return Builder
     */
    public function get_relation_existence_query( Builder $parent_query, $columns = ['count(*)'] ) {
        $query = clone $this;

        // Perform join for complex relations if not already done
        if ( method_exists( $query, 'perform_join' ) ) {
            $query->perform_join();
        }

        // Add basic existence constraints (polymorphic types, etc.)
        $query->add_existence_constraints();

        return $query->select( $columns )
        ->where_column(
            $this->get_relation_existence_compare_key(),
            '=',
            "{$parent_query->as}.{$this->get_parent_key_name()}"
        );
    }

    /**
     * Add the constraints for a relationship existence query.
     *
     * @return void
     */
    protected function add_existence_constraints() {}

    /**
     * Get all of the primary keys for an array of models.
     *
     * @param  array   $models
     * @param  string  $key
     * @return array
     */
    protected function get_keys( array $models, $key ) {
        return array_unique(
            array_values(
                array_map(
                    function ( $model ) use ( $key ) {
                        return is_object( $model ) ? $model->{$key} : $model[$key];
                    }, $models 
                ) 
            ) 
        );
    }
}

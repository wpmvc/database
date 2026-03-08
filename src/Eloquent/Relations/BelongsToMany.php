<?php
/**
 * BelongsToMany relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Query\Builder;

/**
 * Class BelongsToMany
 *
 * Defines a many-to-many relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class BelongsToMany extends Relation {
    /**
     * The pivot model instance or table name.
     *
     * @var Model|string
     */
    public $pivot;

    /**
     * The foreign pivot key on the pivot table.
     *
     * @var string
     */
    public $foreign_pivot_key;

    /**
     * The local pivot key on the pivot table.
     *
     * @var string
     */
    public $local_pivot_key;

    /**
     * Indicates if the join has been performed.
     *
     * @var bool
     */
    protected $performed_join = false;

    /**
     * Create a new belongs to many relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  string   $pivot
     * @param  string   $foreign_pivot_key
     * @param  string   $local_pivot_key
     * @param  string   $foreign_key
     * @param  string   $local_key
     */
    public function __construct( Builder $query, Model $parent, $pivot, $foreign_pivot_key, $local_pivot_key, $foreign_key, $local_key ) {
        if ( is_string( $pivot ) && class_exists( $pivot ) ) {
            $this->pivot = new $pivot;
        } else {
            $this->pivot = $pivot;
        }
        $this->foreign_pivot_key = $foreign_pivot_key;
        $this->local_pivot_key   = $local_pivot_key;

        parent::__construct( $query, $parent, $foreign_key, $local_key );
    }

    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        $this->perform_join();

        $this->where( $this->get_qualified_foreign_pivot_key(), '=', $this->parent->{$this->local_key} );
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {
        $this->perform_join();

        $this->where_in(
            $this->get_qualified_foreign_pivot_key(), $this->get_keys( $models, $this->local_key )
        );
    }

    /**
     * Perform the join for the relationship.
     *
     * @param  Builder|null  $query
     * @return void
     */
    protected function perform_join( $query = null ) {
        if ( $this->performed_join ) {
            return;
        }

        $query       = $query ?: $this;
        $pivot_table = $this->get_pivot_table();

        $query->join( $pivot_table, $this->get_qualified_local_pivot_key(), '=', $this->related->get_qualified_key_name() );

        if ( empty( $query->columns ) ) {
            $query->select( [$this->related::get_table_name() . '.*'] );
        }

        // Pivot Aliasing Guardrail
        $query->add_select(
            [
                "{$pivot_table}.{$this->foreign_pivot_key} as pivot_{$this->foreign_pivot_key}",
                "{$pivot_table}.{$this->local_pivot_key} as pivot_{$this->local_pivot_key}"
            ] 
        );

        $this->performed_join = true;
    }

    /**
     * Get the name of the pivot table.
     *
     * @return string
     */
    public function get_pivot_table() {
        return $this->pivot instanceof Model ? $this->pivot::get_table_name() : $this->pivot;
    }

    /**
     * Get the fully qualified foreign pivot key name.
     *
     * @return string
     */
    public function get_qualified_foreign_pivot_key() {
        return $this->get_pivot_table() . '.' . $this->foreign_pivot_key;
    }

    /**
     * Get the fully qualified local pivot key name.
     *
     * @return string
     */
    public function get_qualified_local_pivot_key() {
        return $this->get_pivot_table() . '.' . $this->local_pivot_key;
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    protected function init_relation( array $models, string $relation ) {
        foreach ( $models as $model ) {
            $model->set_relation( $relation, $this->related->new_collection() );
        }

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
        $dictionary = $this->build_dictionary( $results );

        foreach ( $models as $model ) {
            $key = $model->{$this->local_key};

            if ( isset( $dictionary[$key] ) ) {
                $model->set_relation( $relation, $this->get_results_as_collection( $dictionary[$key] ) );
            } else {
                $model->set_relation( $relation, $this->related->new_collection() );
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  array  $results
     * @return array
     */
    protected function build_dictionary( array $results ) {
        $dictionary = [];
        $pivot_key  = "pivot_{$this->foreign_pivot_key}";

        foreach ( $results as $result ) {
            $dictionary[$result->{$pivot_key}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function get_results() {
        $this->add_constraints();
        return $this->get();
    }

    /**
     * Attach a model instance to the parent model.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return void
     */
    public function attach( $id, array $attributes = [] ) {
        $pivot_table = $this->get_pivot_table();
        $parent_id   = $this->parent->{$this->local_key};

        $exists = $this->parent->new_query()
            ->from( $pivot_table )
            ->where( $this->foreign_pivot_key, $parent_id )
            ->where( $this->local_pivot_key, $id )
            ->exists();

        if ( $exists ) {
            return;
        }

        $this->parent->query()->from( $pivot_table )->insert(
            array_merge(
                [
                    $this->foreign_pivot_key => $parent_id,
                    $this->local_pivot_key   => $id,
                ], $attributes
            )
        );
    }

    public function get_relation_existence_compare_key() {
        return $this->get_qualified_foreign_pivot_key();
    }

    public function get_parent_key_name() {
        return $this->local_key;
    }
}
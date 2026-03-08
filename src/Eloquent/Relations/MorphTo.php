<?php
/**
 * MorphTo relationship class.
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
 * Class MorphTo
 *
 * Defines a polymorphic inverse one-to-one or many relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class MorphTo extends Relation {
    /**
     * The morph name of the relationship.
     *
     * @var string
     */
    public $morph_name;

    /**
     * The type column for the polymorphic relationship.
     *
     * @var string
     */
    public $type_column;

    /**
     * The ID column for the polymorphic relationship.
     *
     * @var string
     */
    public $id_column;

    /**
     * Create a new morph to relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  string   $morph_name
     * @param  string   $type_column
     * @param  string   $id_column
     * @param  string   $local_key
     */
    public function __construct( Builder $query, Model $parent, $morph_name, $type_column, $id_column, $local_key ) {
        $this->morph_name  = $morph_name;
        $this->type_column = $type_column;
        $this->id_column   = $id_column;

        parent::__construct( $query, $parent, $id_column, $local_key );
    }

    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        // MorphTo constraints are dynamic and depend on the parent's current type.
        if ( $this->parent->{$this->type_column} ) {
            $this->where( $this->local_key, '=', $this->parent->{$this->id_column} );
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {
        // Eager loading MorphTo is handled via specialized logic in the orchestrator
        // because it requires querying multiple different tables.
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
            $model->set_relation( $relation, null );
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
        $dictionary = [];

        foreach ( $results as $result ) {
            $type = $result->_morph_type ?? null;
            if ( $type ) {
                $dictionary[$type][$result->{$this->local_key}] = $result;
            }
        }

        foreach ( $models as $model ) {
            $type = $model->{$this->type_column};
            $id   = $model->{$this->id_column};

            if ( isset( $dictionary[$type][$id] ) ) {
                $model->set_relation( $relation, $dictionary[$type][$id] );
            } else {
                $model->set_relation( $relation, null );
            }
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function get_results() {
        $type = $this->parent->{$this->type_column};

        if ( ! $type ) {
            return null;
        }

        $actual_class = Model::get_actual_class_for_morph( $type );

        if ( ! class_exists( $actual_class ) ) {
            return null;
        }

        $model = new $actual_class;

        return $model->new_query()->where( $this->local_key, '=', $this->parent->{$this->id_column} )->first();
    }
}

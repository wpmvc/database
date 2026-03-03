<?php
/**
 * BelongsTo relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

/**
 * Class BelongsTo
 *
 * Defines an inverse one-to-one or many relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class BelongsTo extends Relation {
    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        $this->where( $this->local_key, '=', $this->parent->{$this->foreign_key} );
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {
        $this->where_in(
            $this->local_key, $this->get_keys( $models, $this->foreign_key )
        );
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
            $dictionary[$result->{$this->local_key}] = $result;
        }

        foreach ( $models as $model ) {
            if ( isset( $dictionary[$model->{$this->foreign_key}] ) ) {
                $model->set_relation( $relation, $dictionary[$model->{$this->foreign_key}] );
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
        $this->add_constraints();
        return $this->first();
    }

    /**
     * Get the key for comparing relationship existence.
     *
     * @return string
     */
    public function get_relation_existence_compare_key() {
        return $this->related::get_table_name() . '.' . $this->local_key;
    }

    /**
     * Get the name of the parent key.
     *
     * @return string
     */
    public function get_parent_key_name() {
        return $this->foreign_key;
    }
}

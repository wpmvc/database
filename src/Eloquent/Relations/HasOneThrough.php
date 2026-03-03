<?php
/**
 * HasOneThrough relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

/**
 * Class HasOneThrough
 *
 * Defines a has-one-through relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class HasOneThrough extends HasManyThrough {
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
        $dictionary = $this->build_dictionary( $results );

        foreach ( $models as $model ) {
            $key = $model->{$this->local_key};

            if ( isset( $dictionary[$key] ) ) {
                $model->set_relation( $relation, reset( $dictionary[$key] ) );
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
        return $this->first();
    }
}

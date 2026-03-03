<?php
/**
 * HasOne relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

/**
 * Class HasOne
 *
 * Defines a one-to-one relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class HasOne extends HasOneOrMany {
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
     * Get the default value for the relationship.
     *
     * @return null
     */
    protected function get_default_value() {
        return null;
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
}

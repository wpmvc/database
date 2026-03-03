<?php
/**
 * HasOneOrMany relationship base class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

use RuntimeException;
use WpMVC\Database\Eloquent\Model;

/**
 * Class HasOneOrMany
 *
 * The base class for HasOne and HasMany relationships.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
abstract class HasOneOrMany extends Relation {
    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        $this->where( $this->foreign_key, '=', $this->parent->{$this->local_key} );
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {
        $this->where_in(
            $this->foreign_key, $this->get_keys( $models, $this->local_key )
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
            $model->set_relation( $relation, $this->get_default_value() );
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
                $model->set_relation( $relation, $this->get_default_value() );
            }
        }

        return $models;
    }

    /**
     * Get the default value for the relationship.
     *
     * @return mixed
     */
    abstract protected function get_default_value();

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
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  array  $results
     * @return array
     */
    protected function build_dictionary( array $results ) {
        $dictionary = [];

        foreach ( $results as $result ) {
            $dictionary[$result->{$this->foreign_key}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Build a new instance of the related model with the FK set, without saving.
     *
     * @param  array  $attributes
     * @return Model
     */
    public function make( array $attributes = [] ) {
        $class    = get_class( $this->related );
        $instance = new $class( $attributes );

        $instance->set_attribute( $this->foreign_key, $this->parent->{$this->local_key} );

        return $instance;
    }

    /**
     * Create and persist a new instance of the related model.
     *
     * @param  array  $attributes
     * @return Model|false  Returns the saved model, or false if save was cancelled by an event.
     * @throws RuntimeException  If the parent model has not been saved yet.
     */
    public function create( array $attributes = [] ) {
        if ( ! $this->parent->exists ) {
            throw new RuntimeException(
                sprintf(
                    'Cannot create a related [%s]: the parent [%s] has not been saved yet.',
                    get_class( $this->related ),
                    get_class( $this->parent )
                )
            );
        }

        $instance = $this->make( $attributes );

        if ( ! $instance->save() ) {
            return false;
        }

        return $instance;
    }
}

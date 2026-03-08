<?php
/**
 * HasManyThrough relationship class.
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
 * Class HasManyThrough
 *
 * Defines a has-many-through relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class HasManyThrough extends Relation {
    /**
     * The "through" model instance.
     *
     * @var Model
     */
    public Model $through;

    /**
     * The first foreign key on the through model.
     *
     * @var string
     */
    public $first_key;

    /**
     * The second foreign key on the related model.
     *
     * @var string
     */
    public $second_key;

    /**
     * The second local key on the through model.
     *
     * @var string
     */
    public $second_local_key;

    /**
     * Optional morph type column and value for filtering
     * when the second (distant) key is a polymorphic foreign key.
     *
     * @var string|null
     */
    public ?string $second_key_type_column = null;

    /**
     * Optional morph type value for filtering.
     *
     * @var string|null
     */
    public ?string $second_key_type_value = null;

    /**
     * Indicates if the join has been performed.
     *
     * @var bool
     */
    protected $performed_join = false;

    /**
     * Create a new has many through relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  Model    $through
     * @param  string   $first_key
     * @param  string   $second_key
     * @param  string   $local_key
     * @param  string   $second_local_key
     */
    public function __construct( Builder $query, Model $parent, Model $through, $first_key, $second_key, $local_key, $second_local_key ) {
        $this->through          = $through;
        $this->first_key        = $first_key;
        $this->second_key       = $second_key;
        $this->second_local_key = $second_local_key;

        parent::__construct( $query, $parent, $second_key, $local_key );
    }

    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        $this->perform_join();

        $this->where( $this->get_qualified_first_key(), '=', $this->parent->{$this->local_key} );
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
            $this->get_qualified_first_key(), $this->get_keys( $models, $this->local_key )
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

        $query         = $query ?: $this;
        $through_table = $this->through::get_table_name();

        $query->join( $through_table, $this->get_qualified_second_key_on_through(), '=', $this->get_qualified_second_local_key() );

        if ( $this->second_key_type_column && $this->second_key_type_value ) {
            $query->where( $this->related->get_table_name() . '.' . $this->second_key_type_column, '=', $this->second_key_type_value );
        }

        if ( empty( $query->columns ) ) {
            $query->select( [$this->related::get_table_name() . '.*'] );
        }

        // Select through key for matching
        $query->add_select( ["{$through_table}.{$this->first_key} as through_{$this->first_key}"] );

        $this->performed_join = true;
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
     * Build model dictionary keyed by the through model's first foreign key.
     *
     * @param  array  $results
     * @return array
     */
    protected function build_dictionary( array $results ) {
        $dictionary  = [];
        $through_key = "through_{$this->first_key}";

        foreach ( $results as $result ) {
            $dictionary[$result->{$through_key}][] = $result;
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
     * Add a morph type constraint for the distant (final) table.
     * Use this when the second_key references a polymorphic relationship.
     *
     * @param  string  $type_column  The type column on the distant table (e.g., 'commentable_type')
     * @param  string  $type_value   The expected morph type value (e.g., 'post')
     * @return $this
     */
    public function where_morph_type( string $type_column, string $type_value ) {
        $this->second_key_type_column = $type_column;
        $this->second_key_type_value  = $type_value;
        return $this;
    }

    /**
     * Get the fully qualified first foreign key.
     *
     * @return string
     */
    protected function get_qualified_first_key() {
        return $this->through::get_table_name() . '.' . $this->first_key;
    }

    /**
     * Get the fully qualified second foreign key on the through model.
     *
     * @return string
     */
    protected function get_qualified_second_key_on_through() {
        return $this->through::get_table_name() . '.' . $this->second_local_key;
    }

    /**
     * Get the fully qualified second local key.
     *
     * @return string
     */
    protected function get_qualified_second_local_key() {
        return $this->related::get_table_name() . '.' . $this->foreign_key;
    }

    /**
     * Get the key for comparing relationship existence.
     *
     * @return string
     */
    public function get_relation_existence_compare_key() {
        return $this->get_qualified_first_key();
    }

    /**
     * Get the name of the parent key.
     *
     * @return string
     */
    public function get_parent_key_name() {
        return $this->local_key;
    }
}

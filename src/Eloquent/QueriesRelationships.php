<?php
/**
 * Trait for handling relationship orchestration.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( "ABSPATH" ) || exit;

use Closure;
use RuntimeException;
use WpMVC\Database\Eloquent\Relations\MorphTo;
use WpMVC\Database\Eloquent\Relations\Relation;
use WpMVC\Database\Query\Builder;

/**
 * Trait QueriesRelationships
 *
 * Provides methods for querying and eager loading relationships.
 *
 * @package WpMVC\Database\Eloquent
 */
trait QueriesRelationships {
    /**
     * Set the relationships that should be eager loaded.
     *
     * @param  string|array  $relations
     * @param  string|(Closure(static): mixed)|array|null $callback
     * @return $this
     */
    public function with( $relations, $callback = null ) {
        if ( ! is_array( $relations ) ) {
            $relations = [$relations => $callback];
        }

        foreach ( $relations as $relation => $callback ) {
            if ( is_int( $relation ) ) {
                $relation = $callback;
            }

            $current = &$this->relations;

            // Traverse the items string and create nested arrays
            $items = explode( '.', $relation );

            /** @var Model $model */
            $model = $this->model;

            foreach ( $items as $key ) {
                if ( ! $model ) {
                    break;
                }

                if ( ! method_exists( $model, $key ) ) {
                    throw new \RuntimeException( sprintf( 'Call to undefined relationship [%s] on model [%s].', $key, get_class( $model ) ) );
                }

                /** @var Relation $relationship */
                $relationship = $model->{$key}();

                if ( ! $relationship instanceof Relation ) {
                    throw new \RuntimeException( sprintf( 'Relationship [%s] on model [%s] must return a Relation instance.', $key, get_class( $model ) ) );
                }

                $model = $relationship->get_related();

                if ( ! isset( $current[$key] ) ) {
                    $query         = clone $relationship;
                    $current[$key] = [
                        'query'    => $query,
                        'children' => []
                    ];
                } else {
                    $query = $current[$key]['query'];
                }
                $current = &$current[$key]['children'];
            }

            // Apply the callback to the last item
            if ( is_callable( $callback ) ) {
                call_user_func( $callback, $query );
            }
        }

        return $this;
    }

    /**
     * Add subselect queries for counts of relationships.
     *
     * @param  mixed  $relations
     * @param  callable|null  $callback
     * @return $this
     */
    public function with_count( $relations, $callback = null ) {
        return $this->with_aggregate( $relations, 'count', '*', $callback );
    }

    /**
     * Add subselect queries for sums of relationship columns.
     *
     * @param  mixed  $relation
     * @param  string $column
     * @param  callable|null  $callback
     * @return $this
     */
    public function with_sum( $relation, $column, $callback = null ) {
        return $this->with_aggregate( $relation, 'sum', $column, $callback );
    }

    /**
     * Add subselect queries for averages of relationship columns.
     *
     * @param  mixed  $relation
     * @param  string $column
     * @param  callable|null  $callback
     * @return $this
     */
    public function with_avg( $relation, $column, $callback = null ) {
        return $this->with_aggregate( $relation, 'avg', $column, $callback );
    }

    /**
     * Add subselect queries for minimums of relationship columns.
     *
     * @param  mixed  $relation
     * @param  string $column
     * @param  callable|null  $callback
     * @return $this
     */
    public function with_min( $relation, $column, $callback = null ) {
        return $this->with_aggregate( $relation, 'min', $column, $callback );
    }

    /**
     * Add subselect queries for maximums of relationship columns.
     *
     * @param  mixed  $relation
     * @param  string $column
     * @param  callable|null  $callback
     * @return $this
     */
    public function with_max( $relation, $column, $callback = null ) {
        return $this->with_aggregate( $relation, 'max', $column, $callback );
    }

    /**
     * Add subselect queries for relationship aggregates.
     *
     * @param  mixed   $relations
     * @param  string  $function
     * @param  string  $column
     * @param  callable|null  $callback
     * @return $this
     */
    public function with_aggregate( $relations, $function, $column = '*', $callback = null ) {
        if ( is_array( $relations ) ) {
            foreach ( $relations as $key => $value ) {
                if ( is_numeric( $key ) ) {
                    $this->with_aggregate( $value, $function, $column );
                } else {
                    $this->with_aggregate( $key, $function, $column, $value );
                }
            }
            return $this;
        }

        if ( empty( $this->columns ) ) {
            $this->select( ["{$this->as}.*"] );
        }

        $relation_keys = explode( ' as ', $relations );
        $name          = $relation_keys[0];

        if ( ! method_exists( $this->model, $name ) ) {
            throw new \RuntimeException( sprintf( 'Call to undefined relationship [%s] on model [%s].', $name, get_class( $this->model ) ) );
        }

        /** @var Relation $relationship */
        $relationship = $this->model->{$name}();

        if ( ! $relationship instanceof Relation ) {
            throw new RuntimeException( sprintf( 'Relationship [%s] on model [%s] must return a Relation instance.', $name, get_class( $this->model ) ) );
        }

        $wrapped_column = $column === '*' ? '*' : $this->get_grammar()->wrap( $relationship->get_related()::get_table_name() . '.' . $column );
        $query          = $relationship->get_relation_existence_query( $this, ["{$function}({$wrapped_column})"] );

        if ( is_callable( $callback ) ) {
            call_user_func( $callback, $query );
        }

        $total_key = isset( $relation_keys[1] ) ? $relation_keys[1] : $name . '_' . $function . ( $column === '*' ? '' : '_' . $column );

        $this->columns[$total_key] = $query;

        return $this;
    }

    protected function process_relationships( $parent_items, array $relations, Model $model ) {
        if ( empty( $relations ) || empty( $parent_items ) || ! is_array( $parent_items ) ) {
            return $parent_items;
        }

        foreach ( $relations as $key => $relation_data ) {
            // 1. Get the Relationship instance
            /** @var Relation $relationship */
            $relationship = isset( $relation_data['query'] ) ? $relation_data['query'] : $model->$key();

            // 2. Initialize the relationship on all parent items (sets default null/empty array)
            $parent_items = $relationship->init_relation( $parent_items, $key );

            // 3. Handle MorphTo separately as it involves multiple tables
            if ( $relationship instanceof MorphTo ) {
                $results = $this->process_morph_to( $parent_items, $relationship, $relation_data );
            } else {
                // 4. Set eager constraints on the relationship's query
                $relationship->add_eager_constraints( $parent_items );

                // 5. Execute the relationship query
                $results = $relationship->get_eager();

                // 6. Handle nested eager loading recursively
                $all_sub_relations = array_merge( $relationship->get_relations(), $relation_data['children'] );
                if ( ! empty( $all_sub_relations ) ) {
                    $results_array = $results instanceof \WpMVC\Database\Eloquent\Collection ? $results->all() : (array) $results;
                    $this->process_relationships( $results_array, $all_sub_relations, $relationship->get_related() );
                }
            }

            // 8. Match the results back to their parent models
            $results_array = $results instanceof \WpMVC\Database\Eloquent\Collection ? $results->all() : (array) $results;
            $parent_items  = $relationship->match( $parent_items, $results_array, $key );
        }

        return $parent_items;
    }

    /**
     * Add a relationship existence condition to the query.
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int     $count
     * @param  string  $boolean
     * @param  Closure|null  $callback
     * @return $this
     */
    public function has( $relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null ) {
        if ( is_string( $relation ) ) {
            if ( strpos( $relation, '.' ) !== false ) {
                return $this->has_nested( $relation, $operator, $count, $boolean, $callback );
            }

            $relationship = $this->model->$relation();
        } else {
            $relationship = $relation;
        }

        $is_existence = $operator === '>=' && $count === 1;
        $query        = $relationship->get_relation_existence_query( $this, $is_existence ? ['*'] : ['count(*)'] );

        if ( $callback ) {
            call_user_func( $callback, $query );
        }

        return $this->add_has_where(
            $query, $operator, $count, $boolean
        );
    }

    /**
     * Add a relationship existence condition to the query with a callback.
     *
     * @param  string    $relation
     * @param  Closure|null  $callback
     * @param  string    $operator
     * @param  int       $count
     * @return $this
     */
    public function where_has( $relation, $callback = null, $operator = '>=', $count = 1 ) {
        return $this->has( $relation, $operator, $count, 'and', $callback );
    }

    /**
     * Add a relationship existence condition to the query with an "or".
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int     $count
     * @return $this
     */
    public function or_has( $relation, $operator = '>=', $count = 1 ) {
        return $this->has( $relation, $operator, $count, 'or' );
    }

    /**
     * Add a relationship existence condition to the query with a callback and "or".
     *
     * @param  string    $relation
     * @param  Closure|null  $callback
     * @param  string    $operator
     * @param  int       $count
     * @return $this
     */
    public function or_where_has( $relation, $callback = null, $operator = '>=', $count = 1 ) {
        return $this->has( $relation, $operator, $count, 'or', $callback );
    }

    /**
     * Add a relationship absence condition to the query.
     *
     * @param  string  $relation
     * @param  string  $boolean
     * @param  Closure|null  $callback
     * @return $this
     */
    public function doesnt_have( $relation, $boolean = 'and', $callback = null ) {
        $relationship = $this->model->$relation();

        $query = $relationship->get_relation_existence_query( $this, ['*'] );

        if ( $callback ) {
            call_user_func( $callback, $query );
        }

        return $boolean === 'and'
            ? $this->where_not_exists( $query )
            : $this->or_where_not_exists( $query );
    }

    /**
     * Add a relationship absence condition to the query with a callback.
     *
     * @param  string  $relation
     * @param  Closure|null  $callback
     * @return $this
     */
    public function where_doesnt_have( $relation, $callback = null ) {
        return $this->doesnt_have( $relation, 'and', $callback );
    }

    /**
     * Add a relationship absence condition to the query with an "or".
     *
     * @param  string  $relation
     * @return $this
     */
    public function or_doesnt_have( $relation ) {
        return $this->doesnt_have( $relation, 'or' );
    }

    /**
     * Add a relationship absence condition to the query with a callback and "or".
     *
     * @param  string  $relation
     * @param  Closure|null  $callback
     * @return $this
     */
    public function or_where_doesnt_have( $relation, $callback = null ) {
        return $this->doesnt_have( $relation, 'or', $callback );
    }

    /**
     * Add nested relationship existence conditions to the query.
     *
     * @param  string  $relations
     * @param  string  $operator
     * @param  int     $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return $this
     */
    protected function has_nested( $relations, $operator = '>=', $count = 1, $boolean = 'and', $callback = null ) {
        $relations = explode( '.', $relations );

        $closure = function( $q ) use ( &$closure, &$relations, $operator, $count, $callback ) {
            if ( count( $relations ) > 1 ) {
                $q->where_has( array_shift( $relations ), $closure );
            } else {
                $q->has( array_shift( $relations ), $operator, $count, 'and', $callback );
            }
        };

        return $this->has( array_shift( $relations ), '>=', 1, $boolean, $closure );
    }

    /**
     * Add the "has" condition where clause to the query.
     *
     * @param  Builder  $has_query
     * @param  string   $operator
     * @param  int      $count
     * @param  string   $boolean
     * @return $this
     */
    protected function add_has_where( Builder $has_query, $operator, $count, $boolean ) {
        if ( $operator === '>=' && $count === 1 ) {
            return $boolean === 'and' 
                ? $this->where_exists( $has_query ) 
                : $this->or_where_exists( $has_query );
        }

        return $boolean === 'and'
            ? $this->where( $has_query, $operator, $count )
            : $this->or_where( $has_query, $operator, $count );
    }

    /**
     * Processes a polymorphic inverse relationship (MorphTo).
     * Collects all unique types from the parent items and fetches related models.
     *
     * @param array $parent_items
     * @param MorphTo $relationship
     * @param array $relation_data
     * @return array
     */
    protected function process_morph_to( array $parent_items, MorphTo $relationship, array $relation_data ) {
        $items_by_type = [];
        foreach ( $parent_items as $item ) {
            $type = $item->{$relationship->type_column};
            if ( empty( $type ) ) {
                continue;
            }

            // Resolve morph alias to actual class BEFORE checking class_exists
            $actual_class = Model::get_actual_class_for_morph( $type );
            if ( ! class_exists( $actual_class ) ) {
                continue;
            }

            $items_by_type[$type][] = $item->{$relationship->id_column};
        }

        $all_results = [];

        foreach ( $items_by_type as $type => $ids ) {
            $actual_class = Model::get_actual_class_for_morph( $type );

            /** @var Model $model */
            $model = new $actual_class;
            $query = clone $relation_data['query'];
            $query->set_model( $model )->from( $model::get_table_name() )->where_in( $relationship->local_key, array_unique( $ids ) );

            global $wpdb;
            //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $results = $wpdb->get_results( $query->to_sql() );
            $items   = $query->hydrate( is_array( $results ) ? $results : [] );
            
            $all_sub_relations = array_merge( $query->get_relations(), $relation_data['children'] );
            $processed         = $this->process_relationships( $items, $all_sub_relations, $model );

            // Tag results with their type for unambiguous mapping
            foreach ( $processed as $result ) {
                $result->_morph_type = $type;
            }

            $all_results = array_merge( $all_results, $processed );
        }

        return $all_results;
    }
}
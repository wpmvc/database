<?php
/**
 * Query Builder class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Query;

defined( "ABSPATH" ) || exit;

use DateTime;
use InvalidArgumentException;
use Closure;
use Generator;
use Exception;
use Throwable;
use BadMethodCallException;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\QueriesRelationships;
use WpMVC\Database\Query\Grammar;
use WpMVC\Database\Query\JoinClause;
use WpMVC\Database\Clauses\WhereClause;
use WpMVC\Database\Clauses\HavingClause;
use WpMVC\Database\Pagination\Paginator;
use wpdb;
use stdClass;
use WpMVC\Database\Eloquent\Collection;

/**
 * Class Builder
 *
 * The fluent query builder for constructing database queries.
 *
 * @package WpMVC\Database\Query
 *
 * @method $this with(string|array $relations, string|(Closure(Model): mixed)|array|null $callback = null)
 * @method $this with_count(mixed $relations, callable|null $callback = null)
 * @method $this with_sum(string|array $relation, string $column, callable|null $callback = null)
 * @method $this with_avg(string|array $relation, string $column, callable|null $callback = null)
 * @method $this with_min(string|array $relation, string $column, callable|null $callback = null)
 * @method $this with_max(string|array $relation, string $column, callable|null $callback = null)
 * @method $this where(string|callable|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method $this where_in(string $column, array $values, string $boolean = 'and', bool $not = false)
 * @method $this where_not_in(string $column, array $values, string $boolean = 'and')
 * @method $this where_null(string $column, string $boolean = 'and', bool $not = false)
 * @method $this where_not_null(string $column, string $boolean = 'and')
 * @method $this or_where(string|callable|Closure $column, mixed $operator = null, mixed $value = null)
 * @method $this having(string $column, string $operator, mixed $value, string $boolean = 'and')
 * @method Collection all()
 * @method Model first_or_fail()
 */
class Builder {

    use WhereClause, HavingClause, QueriesRelationships;

    /**
     * The current query value bindings.
     *
     * @var array
     */
    protected $bindings = [
        'select'  => [],
        'from'    => [],
        'join'    => [],
        'where'   => [],
        'groupBy' => [],
        'having'  => [],
        'order'   => [],
        'union'   => [],
        'limit'   => [],
        'offset'  => [],
    ];

    /**
     * The model being queried.
     *
     * @var \WpMVC\Database\Eloquent\Model|null
     */
    public $model;

    /**
     * The table which the query is targeting.
     *
     * @var string
     */
    public $from;

    /**
     * The table alias for the query.
     *
     * @var string
     */
    public $as;

    /**
     * The groupings for the query.
     *
     * @var array
     */
    public $groups;

    /**
     * An aggregate function and column to be run.
     *
     * @var array|null
     */
    public $aggregate;

    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public $columns = [];

    /**
     * The query unions.
     *
     * @var array
     */
    public $unions = [];

    /**
     * Indicates if the query unions are "all".
     *
     * @var bool
     */
    public $union_all = false;

    /**
     * Indicates if the query returns distinct results.
     *
     * @var bool|array
     */
    public $distinct = false;

     /**
     * The table joins for the query.
     *
     * @var array
     */
    public $joins = [];

     /**
     * The table limit for the query.
     *
     * @var int
     */
    public $limit;

    /**
     * The orderings for the query.
     *
     * @var array
     */
    public $orders;

    /**
     * The number of records to skip.
     *
     * @var int|null
     */
    public $offset;

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The relationships that should be counted.
     *
     * @var array
     */
    public $count_relations = [];

    /**
     * All of the available clause operators.
     *
     * @var string[]
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    public function __construct( ?Model $model = null ) {
        $this->model = $model;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return $this
     */
    public function new_query() {
        return new static( $this->model );
    }

    /**
     * Clone the query builder instance.
     *
     * @return void
     */
    public function __clone() {
        if ( is_array( $this->joins ) ) {
            $this->joins = array_map(
                function ( $join ) {
                    return clone $join;
                }, $this->joins 
            );
        }

        if ( is_array( $this->relations ) ) {
            $this->relations = $this->clone_relations( $this->relations );
        }

        if ( is_array( $this->bindings ) ) {
            foreach ( $this->bindings as $key => $values ) {
                $this->bindings[$key] = array_map(
                    function ( $binding ) {
                        return is_object( $binding ) ? clone $binding : $binding;
                    }, $values 
                );
            }
        }

        if ( is_array( $this->clauses ) ) {
             $this->clauses = $this->clone_clauses( $this->clauses );
        }
    }

    /**
     * Deep clone the clauses array.
     *
     * @param  array  $clauses
     * @return array
     */
    protected function clone_clauses( array $clauses ) {
        $cloned = [];
        foreach ( $clauses as $key => $value ) {
            if ( is_array( $value ) ) {
                $cloned[$key] = $this->clone_clauses( $value );
            } elseif ( $key === 'query' && is_object( $value ) ) {
                $cloned[$key] = clone $value;
            } else {
                $cloned[$key] = $value;
            }
        }
        return $cloned;
    }

    /**
     * Deep clone the relations array.
     *
     * @param  array  $relations
     * @return array
     */
    protected function clone_relations( array $relations ) {
        foreach ( $relations as $key => $data ) {
            $relations[$key]['query'] = clone $data['query'];

            if ( ! empty( $data['children'] ) ) {
                $relations[$key]['children'] = $this->clone_relations( $data['children'] );
            }
        }

        return $relations;
    }

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
     * @param Model $model
     * @return $this
     */
    public function set_model( Model $model ) {
        $this->model = $model;
        return $this;
    }

    /**
     * Set the current query value bindings.
     *
     * @param  array   $bindings
     * @param  string  $type
     * @return $this
     *
     * @throws InvalidArgumentException  If the binding type is invalid.
     */
    public function set_bindings( array $bindings, $type = 'where' ) {
        if ( ! array_key_exists( $type, $this->bindings ) ) {
            throw new \InvalidArgumentException( "Invalid binding type: {$type}." );
        }

        $this->bindings[$type] = $bindings;

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed   $value
     * @param  string  $type
     * @return $this
     *
     * @throws InvalidArgumentException  If the binding type is invalid.
     */
    public function add_binding( $value, $type = 'where' ) {
        if ( ! array_key_exists( $type, $this->bindings ) ) {
            throw new \InvalidArgumentException( "Invalid binding type: {$type}." );
        }

        if ( is_array( $value ) ) {
            $this->bindings[$type] = array_values( array_merge( $this->bindings[$type], $value ) );
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Merge the bindings from another builder.
     *
     * @param  self  $query
     * @return $this
     */
    public function merge_bindings( self $query, ?string $target_category = null ) {
        if ( $target_category ) {
            foreach ( $query->get_bindings() as $binding ) {
                $this->add_binding( $binding, $target_category );
            }
        } else {
            $this->bindings = array_merge( $this->bindings, $query->get_raw_bindings() );
        }

        return $this;
    }

    /**
     * @return array
     */
    public function get_relations() {
        return $this->relations;
    }

    /**
     * Get the array of "on" clauses for join.
     *
     * @return array
     */
    public function get_ons(): array {
        return [];
    }

    /**
     * Set the aggregate information and get the SQL query.
     *
     * @param  string  $function
     * @param  array   $columns
     * @return string
     */
    public function aggregate_to_sql( $function, $columns = ['*'] ) {
        $this->aggregate = compact( 'function', 'columns' );
        return $this->to_sql();
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param  string  $table
     * @param  string|null  $as
     * @return $this
     */
    public function from( string $table, $as = null ) {
        if ( $this->model ) {
            $this->from = $this->model->resolver()->table( $table );
        } else {
            $this->from = $table;
        }
        $this->as = is_null( $as ) ? $table : $as;
        return $this;
    }

    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select( $columns = ['*'] ) {
        $this->columns = is_array( $columns ) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function add_select( $columns ) {
        $columns = is_array( $columns ) ? $columns : func_get_args();

        foreach ( $columns as $as => $column ) {
            if ( is_string( $as ) ) {
                $this->columns[$as] = $column;
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @param  mixed  ...$distinct
     * @return $this
     */
    public function distinct() {
        $this->distinct = true;
        return $this;
    }

    /**
     * Apply the callback if the given "value" is (or resolves to) true.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this|mixed
     */
    public function when( $value, $callback, $default = null ) {
        if ( $value ) {
            return $callback( $this, $value ) ?: $this;
        } elseif ( $default ) {
            return $default( $this, $value ) ?: $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is (or resolves to) false.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this|mixed
     */
    public function unless( $value, $callback, $default = null ) {
        if ( ! $value ) {
            return $callback( $this, $value ) ?: $this;
        } elseif ( $default ) {
            return $default( $this, $value ) ?: $this;
        }

        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function tap( $callback ) {
        $callback( $this );
        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * @param  string  $table
     * @param  Closure(JoinClause):mixed|array|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool    $where
     * @return $this
     */
    public function join( $table, $first, $operator = null, $second = null, $type = 'inner', $where = false ) {

        $join = new JoinClause( $table, $type, $this->model );

        if ( $first instanceof \Closure ) {
            call_user_func( $first, $join );
        } else {
            if ( $where ) {
                $join->where( $first, $operator, $second );
            } else {
                $join->on_column( $first, $operator, $second );
            }
        }

        $this->joins[] = $join;
        return $this;
    }

    /**
     * Add a left join to the query.
     *
     * @param  string  $table
     * @param  Closure(JoinClause):mixed|array|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  bool    $where
     * @return $this
     */
    public function left_join( $table, $first, $operator = null, $second = null, $where = false ) {
        return $this->join( $table, $first, $operator, $second, 'left', $where );
    }

    /**
     * Add a right join to the query.
     *
     * @param  string  $table
     * @param  Closure(JoinClause):mixed|array|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  bool    $where
     * @return $this
     */
    public function right_join( $table, $first, $operator = null, $second = null, $where = false ) {
        return $this->join( $table, $first, $operator, $second, 'right', $where );
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array|string  ...$groups
     * @return $this
     */
    public function group_by( $groups ) {
        $this->groups = is_array( $groups ) ? $groups : func_get_args();
        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function order_by( $column, $direction = 'asc' ) {
        $direction = strtolower( $direction );

        if ( ! in_array( $direction, ['asc', 'desc'], true ) ) {
            throw new InvalidArgumentException( 'Order direction must be "asc" or "desc".' );
        }

        $this->orders[] = [
            'column'    => $column,
            'direction' => $direction,
        ];
        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function order_by_desc( $column ) {
        return $this->order_by( $column, 'desc' );
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function latest( string $column = 'created_at' ) {
        return $this->order_by( $column, 'desc' );
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function oldest( string $column = 'created_at' ) {
        return $this->order_by( $column, 'asc' );
    }

    /**
     * Add an "order by raw" clause to the query.
     *
     * @param  string  $sql
     * @return $this
     */
    public function order_by_raw( string $sql ) {
        $this->orders[] = ['column' => $sql, 'direction' => ''];
        return $this;
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function offset( ?int $value ) {
        if ( is_null( $value ) || $value < 0 ) {
            $this->offset = null;
        } else {
            $this->offset = $value;
        }
        return $this;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function limit( ?int $value ) {
        if ( is_null( $value ) || $value < 0 ) {
            $this->limit = null;
        } else {
            $this->limit = $value;
        }
        return $this;
    }

    /**
     * Compile the query SQL without resetting bindings.
     *
     * Used internally by union/subquery compilation so bindings
     * remain available to be merged into the parent query.
     *
     * @return string
     */
    public function compile_sql(): string {
        if ( empty( $this->columns ) ) {
            $this->columns = ['*'];
        }
        return $this->get_grammar()->compile_select( $this );
    }

    /**
     * Get the raw SQL representation of the query with placeholders.
     *
     * @return string
     */
    public function get_raw_sql() {
        // Reset bindings before compilation to avoid duplication on repeated calls.
        $this->reset_bindings();
        return $this->compile_sql();
    }

    /**
     * Get the SQL representation of the query with bound values.
     *
     * @return string
     */
    public function to_sql() {
        return $this->bind_values( $this->get_raw_sql() );
    }

    /**
     * Get the grammar instance.
     *
     * @return Grammar
     */
    public function get_grammar() {
        return new Grammar;
    }

    /**
     *
     * @param  array  $values
     * @return string
     */
    public function to_sql_insert( array $values ) {
        return $this->bind_values( $this->get_grammar()->compile_insert( $this, $values ) );
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function to_sql_update( array $values ) {
        return $this->bind_values( $this->get_grammar()->compile_update( $this, $values ) );
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function to_sql_delete() {
        return $this->bind_values( $this->get_grammar()->compile_delete( $this ) );
    }

    /**
     * Map the raw database results into a collection of models.
     *
     * @param  array  $items
     * @return Model[]
     */
    public function hydrate( array $items ) {
        $model = $this->model;

        return array_map(
            function ( $item ) use ( $model ) {
                return $model->new_from_builder( (array) $item );
            }, $items 
        );
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return Collection
     */
    public function get() {
        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results( $this->to_sql() );

        $items = $this->hydrate( is_array( $results ) ? $results : [] );

        if ( ! $this->aggregate ) {
            $items = $this->process_relationships( $items, $this->relations, $this->model );
        }

        if ( $this->model ) {
            return $this->model->new_collection( $items );
        }

        return new \WpMVC\Database\Eloquent\Collection( $items );
    }

    /**
     * Get all of the models from the database.
     *
     * @return Collection
     */
    public function all() {
        return $this->get();
    }

    /**
     * Process results in fixed-size batches.
     *
     * @param  int       $count     Rows per batch.
     * @param  callable  $callback  Called with (Collection $batch, int $page).
     * @return bool  Returns false early if the callback returns false.
     */
    public function chunk( int $count, callable $callback ): bool {
        $page = 1;
        do {
            $results = ( clone $this )->limit( $count )->offset( ( $page - 1 ) * $count )->get();

            if ( $results->count() === 0 ) {
                break;
            }

            if ( $callback( $results, $page ) === false ) {
                return false;
            }

            $page++;
        } while ( $results->count() === $count );

        return true;
    }

    /**
     * Return a Generator that yields one hydrated row at a time.
     *
     * @return Generator
     */
    public function cursor(): \Generator {
        global $wpdb;

        $results = $wpdb->get_results( $this->to_sql() ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        foreach ( $results as $row ) {
            $hydrated = $this->hydrate( [ $row ] );
            yield $hydrated[0] ?? $row;
        }
    }

    /**
     * Execute a callback inside a database transaction.
     * Commits on success, rolls back and re-throws on exception.
     *
     * @param callable $callback
     * @return mixed Return value of the callback.
     * @throws Throwable
     */
    public static function transaction( callable $callback ) {
        global $wpdb;

        $wpdb->query( 'START TRANSACTION' );

        try {
            $result = $callback();
            $wpdb->query( 'COMMIT' );
            return $result;
        } catch ( \Throwable $e ) {
            $wpdb->query( 'ROLLBACK' );
            throw $e;
        }
    }

    /**
     * Add a WHERE EXISTS subquery clause.
     *
     * @param  Closure(Builder):void|Builder  $callback  Closure or an existing Builder subquery.
     * @param  string  $boolean  'and' or 'or'.
     * @param  bool    $not      If true, compiles as WHERE NOT EXISTS.
     * @return $this
     */
    public function where_exists( $callback, string $boolean = 'and', bool $not = false ) {
        if ( $callback instanceof \Closure ) {
            $query = $this->new_query();
            $callback( $query );
        } else {
            $query = $callback; // Already a Builder instance (e.g. from doesnt_have / where_has)
        }
        return $this->clause_exists( 'wheres', $query, null, $boolean, $not );
    }

    /**
     * Add a WHERE NOT EXISTS subquery clause.
     *
     * @param  Closure(Builder):void|Builder  $callback
     * @return $this
     */
    public function where_not_exists( $callback ) {
        return $this->where_exists( $callback, 'and', true );
    }

    /**
     * Add an OR WHERE EXISTS subquery clause.
     *
     * @param  Closure(Builder):void|Builder  $callback
     * @return $this
     */
    public function or_where_exists( $callback ) {
        return $this->where_exists( $callback, 'or' );
    }

    /**
     * Add an OR WHERE NOT EXISTS subquery clause.
     *
     * @param  Closure(Builder):void|Builder  $callback
     * @return $this
     */
    public function or_where_not_exists( $callback ) {
        return $this->where_exists( $callback, 'or', true );
    }

    /**
     * @param  int  $current_page
     * @param  int  $per_page
     * @param  int  $min_per_page
     * @param  int  $max_per_page
     * @return Paginator
     */
    public function paginate( int $current_page, int $per_page = 10, int $min_per_page = 10, int $max_per_page = 100 ) {
        if ( $per_page > $max_per_page || $per_page < $min_per_page ) {
            $per_page = $max_per_page;
        }

        if ( 0 >= $current_page ) {
            $current_page = 1;
        }

        $offset = ( $current_page - 1 ) * $per_page;

        // Calculate total count without limit, offset, or active aggregate.
        $clone = clone $this;
        $total = $clone->limit( null )->offset( null )->count();

        // Get the specific page rows
        $items = $this->limit( $per_page )->offset( $offset )->get();

        return new Paginator( $items, $total, $per_page, $current_page );
    }
    
    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @return Model|stdClass|null|Collection
     */
    public function find( $id ) {
        if ( is_array( $id ) ) {
            return $this->where_in( $this->model->get_key_name(), $id )->get();
        }

        return $this->where( $this->model->get_key_name(), '=', $id )->first();
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @return Model|stdClass
     * @throws Exception
     */
    public function find_or_fail( $id ) {
        $result = $this->find( $id );

        if ( is_null( $result ) ) {
            throw new \Exception( "Model not found." );
        }

        return $result;
    }

    /**
     * Execute the query and get the first result.
     *
     * @return Model|stdClass|null
     */
    public function first() {
        $data = $this->limit( 1 )->get();
        return isset( $data[0] ) ? $data[0] : null;
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @return stdClass|Model
     * @throws Exception
     */
    public function first_or_fail() {
        if ( ! is_null( $result = $this->first() ) ) {
            return $result;
        }

        throw new \Exception( "Model not found." );
    }

    /**
     * Filter the query by primary key.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function where_key( $id ) {
        if ( is_array( $id ) ) {
            $this->where_in( $this->model->get_key_name(), $id );

            return $this;
        }

        return $this->where( $this->model->get_key_name(), '=', $id );
    }

    /**
     * Insert new records into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert( array $values ) {
        $sql = $this->to_sql_insert( $values );
        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (bool) $wpdb->query( $sql );
    }

    /**
     * Insert a new record into the database and get the ID.
     *
     * @param  array  $values
     * @return int
     */
    public function insert_get_id( array $values ) {
        $this->insert( $values );
        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        return $wpdb->insert_id;
    }
    
    /**
     * Update records in the database.
     *
     * @param array $values
     * @return integer
     */
    public function update( array $values ) {
        $sql = $this->to_sql_update( $values );
        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $result = $wpdb->query( $sql );
        return $result;
    }

    /**
     * Delete records from the database.
     *
     * @return mixed
     */
    public function delete() {
        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->query( $this->to_sql_delete() );
    }

    /**
     * Prepare Query Values and return the final SQL.
     *
     * @param string $sql
     * @param bool $clear_bindings
     * @return string
     */
    public function bind_values( string $sql, $clear_bindings = true ) {
        $bindings = $this->get_bindings();

        if ( empty( $bindings ) ) {
            return $sql;
        }

        global $wpdb;
        /**
         * @var wpdb $wpdb
         */
        $sql_query = $wpdb->prepare(
            $sql, ...array_map( //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                function( $value ) {
                    return $value instanceof DateTime ? $value->format( 'Y-m-d H:i:s' ) : $value;
                }, $bindings
            )
        );

        if ( $clear_bindings ) {
            $this->reset_bindings();
        }

        return $sql_query;
    }

    /**
     * Reset the query bindings.
     *
     * @return $this
     */
    public function reset_bindings() {
        foreach ( $this->bindings as $type => $values ) {
            $this->bindings[$type] = [];
        }

        return $this;
    }

    /**
     * Set query values for the using wpdb::prepare and return placeholder.
     *
     * @param mixed  $value
     * @param string $type
     * @return string
     */
    public function set_binding( $value, $type = 'where' ) {
        if ( is_null( $value ) ) {
            return "null";
        }

        $this->add_binding( $value, $type );

        $type_val = gettype( $value );

        if ( 'integer' === $type_val || 'boolean' === $type_val ) {
            return '%d';
        }

        if ( 'double' === $type_val ) {
            return '%f';
        }

        return '%s';
    }

    /**
     * Get the flattened list of bindings in order of SQL execution.
     *
     * @return array
     */
    public function get_bindings() {
        $flat = [];

        foreach ( $this->bindings as $category => $values ) {
            foreach ( $values as $value ) {
                $flat[] = $value;
            }
        }

        return $flat;
    }

    /**
     * Get the raw associative bindings array.
     *
     * @return array
     */
    public function get_raw_bindings() {
        return $this->bindings;
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  array   $columns
     * @return mixed
     */
    public function aggregate( $function, $columns = ['*'] ) {
        $results = $this->set_aggregate( $function, $columns )->get();
        if ( ! isset( $results[0] ) ) {
            return 0;
        }
        return $results[0]->aggregate;
    }

    /**
     * Set the aggregate property without running the query.
     *
     * @param  string  $function
     * @param  array  $columns
     * @return $this
     */
    protected function set_aggregate( $function, $columns ) {
        $this->aggregate = compact( 'function', 'columns' );

        if ( empty( $this->groups ) ) {
            $this->orders = null;
        }

        return $this;
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count( string $column = '*' ) {
        if ( ! empty( $this->groups ) ) {
            // Optimization: Use COUNT(DISTINCT ...) if no HAVING clause is present
            if ( method_exists( $this, 'get_havings' ) && empty( $this->get_havings() ) ) {
                $query         = clone $this;
                $query->groups = null;
                return (int) $query->distinct()->aggregate( __FUNCTION__, $this->groups );
            }

            $query = clone $this;
            return count( $query->set_aggregate( __FUNCTION__, [$column] )->get() );
        }

        return (int) $this->aggregate( __FUNCTION__, [$column] );
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists() {
        return $this->count() > 0;
    }

    /**
     * Determine if no rows exist for the current query.
     *
     * @return bool
     */
    public function doesnt_exist() {
        return ! $this->exists();
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function min( $column ) {
        return $this->aggregate( __FUNCTION__, [$column] );
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function max( $column ) {
        return $this->aggregate( __FUNCTION__, [$column] );
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function sum( $column ) {
        $result = $this->aggregate( __FUNCTION__, [$column] );

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function avg( $column ) {
        return $this->aggregate( __FUNCTION__, [$column] );
    }

      /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $use_default
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function prepare_value_and_operator( $value, $operator, $use_default = false ) {
        if ( $use_default ) {
            return [$operator, '='];
        } elseif ( $this->invalid_operator_and_value( $operator, $value ) ) {
            throw new InvalidArgumentException( 'Illegal operator and value combination.' );
        }

        return [$value, $operator];
    }

     /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalid_operator_and_value( $operator, $value ) {
        return is_null( $value ) && in_array( $operator, $this->operators ) &&
             ! in_array( $operator, ['=', '<>', '!='] );
    }

    /**
     * Add a union statement to the query.
     *
     * @param  Builder|Closure(Builder):void  $query
     * @param  bool  $all
     * @return $this
     */
    public function union( $query, $all = false ) {
        if ( $query instanceof \Closure ) {
            $callback = $query;
            $sub      = $this->new_query();
            // Propagate table context so where_has / from() work inside the closure
            $sub->from = $this->from;
            $sub->as   = $this->as;
            $callback( $sub );
            $query = $sub;
        }

        $this->unions[] = compact( 'query', 'all' );

        return $this;
    }

    /**
     * Add a union all statement to the query.
     *
     * @param  Builder|Closure(Builder):void  $query
     * @return $this
     */
    public function union_all( $query ) {
        return $this->union( $query, true );
    }

    /**
     * Handle dynamic method calls into the builder.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return Builder|mixed
     *
     * @throws BadMethodCallException
     */
    public function __call( $method, $parameters ) {
        if ( $this->model && method_exists( $this->model, $scope = 'scope_' . $method ) ) {
            return $this->model->$scope( $this, ...$parameters ) ?: $this;
        }

        throw new \BadMethodCallException(
            sprintf(
                'Call to undefined method %s::%s()', static::class, $method 
            ) 
        );
    }

    /**
    * Determine if the given operator is supported.
    *
    * @param  string  $operator
    * @return bool
    */
    protected function invalid_operator( $operator ) {
        return ! is_string( $operator ) || ! in_array( strtolower( $operator ), $this->operators, true );
    }
}

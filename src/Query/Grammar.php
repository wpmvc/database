<?php
/**
 * Query Grammar class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Query;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Query\Builder;
use WpMVC\Database\Query\JoinClause;

/**
 * Class Grammar
 *
 * Responsible for compiling various query building components into raw SQL.
 *
 * @package WpMVC\Database\Query
 */
class Grammar {
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $select_components = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'unions'
    ];

    /**
     * Compile a select query into SQL.
     *
     * @param  Builder $query
     * @return string
     */
    public function compile_select( Builder $query ) {
        return $this->concatenate( $this->compile_components( $query ) );
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  Builder $query
     * @param  array  $values
     * @return string
     */
    public function compile_insert( Builder $query, array $values ) {
        if ( ! is_array( reset( $values ) ) ) {
            $values = [$values];
        } else {
            foreach ( $values as $key => $value ) {
                ksort( $value );
                $values[$key] = $value;
            }
        }

        $columns = $this->columnize( $query, array_keys( reset( $values ) ) );

        $parameters = implode(
            ', ', array_map(
                function( $record ) use( $query ) {
                    return '(' . implode(
                        ', ', array_map(
                            function( $item ) use( $query ) {
                                return $query->set_binding( $item, 'select' );
                            }, $record
                        ) 
                    ) . ')';
                }, $values
            )
        );

        $table = $this->wrap( $query->from );

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  Builder $query
     * @param  array  $values
     * @return string
     */
    public function compile_update( Builder $query, array $values ) {
        $keys = array_keys( $values );

        $columns = implode(
            ', ', array_map(
                function( $value, $key ) use( $query ){
                        return $this->wrap( $key ) . ' = ' . $query->set_binding( $value, 'select' );
                }, $values, $keys
            )
        );

        $where = $this->compile_wheres( $query );

        $table = $this->wrap( $query->from );

        return "update {$table} set {$columns} {$where}";
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  Builder $query
     * @return string
     */
    public function compile_delete( Builder $query ) {
        $where = $this->compile_wheres( $query );
        $joins = $this->compile_joins( $query, $query->joins );
        
        $alias = $query->as ? " as " . $this->wrap( $query->as ) : "";
        $from  = $this->wrap( $query->from ) . $alias;

        return "delete " . ( $query->as ? $this->wrap( $query->as ) : $this->wrap( $query->from ) ) . " from {$from} {$joins} {$where}";
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param  Builder  $query
     * @return array
     */
    protected function compile_components( Builder $query ) {
        $sql = [];

        $clauses = $query->get_clauses();

        $has_unions = ! empty( $query->unions );

        // When a UNION is present, defer ORDER BY / LIMIT / OFFSET until after the union SQL
        // so MySQL sees:  SELECT ... UNION SELECT ... ORDER BY ... LIMIT ...
        // rather than:    SELECT ... ORDER BY ... LIMIT ... UNION SELECT ...
        $defer_after_union = $has_unions ? ['orders', 'limit', 'offset'] : [];

        foreach ( $this->select_components as $component ) {
            if ( in_array( $component, $defer_after_union, true ) ) {
                continue; // handled below
            }

            $method = 'compile_' . $component;
            if ( isset( $query->$component ) ) {
                $sql[$component] = $this->$method( $query, $query->$component );
            } elseif ( isset( $clauses[$component] ) ) {
                $sql[$component] = $this->$method( $query, $clauses[$component] );
            }
        }

        // Append deferred ORDER BY / LIMIT / OFFSET after unions
        foreach ( $defer_after_union as $component ) {
            $method = 'compile_' . $component;
            if ( isset( $query->$component ) ) {
                $sql[$component] = $this->$method( $query, $query->$component );
            } elseif ( isset( $clauses[$component] ) ) {
                $sql[$component] = $this->$method( $query, $clauses[$component] );
            }
        }

        return $sql;
    }

    /**
     * Compile the "columns" portion of the query.
     *
     * @param  Builder $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compile_columns( Builder $query, $columns ) {
        if ( ! is_null( $query->aggregate ) ) {
            return;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        return $select . $this->columnize( $query, $columns );
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  Builder $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compile_aggregate( Builder $query, $aggregate ) {
        $column = $this->columnize( $query, $query->aggregate['columns'] );
        
        if ( $query->distinct ) {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as aggregate';
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param  Builder  $query
     * @param string $table
     * @return string
     */
    protected function compile_from( Builder $query, $table ) {
        if ( is_null( $query->as ) || $query->as === $table ) {
            return 'from ' . $this->wrap( $table );
        }
        return "from " . $this->wrap( $table ) . " as " . $this->wrap( $query->as );
    }

    /**
     * Compile the "wheres" portion of the query.
     *
     * @param  Builder  $query
     * @return string
     */
    public function compile_wheres( Builder $query ) {
        if ( empty( $query->get_wheres() ) ) {
            return '';
        }

        return $this->compile_where_or_having( $query, $query->get_wheres(), 'where' );
    }

    /**
     * Compile the "joins" portion of the query.
     *
     * @param  Builder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compile_joins( Builder $query, $joins ) {
        return implode(
            ' ', array_map(
                function( JoinClause $join ) use ( $query ) {
                    if ( ! empty( $join->get_wheres() ) ) {
                          $sql = $join->get_raw_sql();
                          $query->merge_bindings( $join, 'join' );
                          $table = "($sql)";
                    } else {
                        $table = $this->wrap( $join->from );
                    }
        
                    $table .= " as " . $this->wrap( $join->as );
  
                    if ( ! empty( $join->joins ) ) {
                        $table = "({$table} {$this->compile_joins( $join, $join->joins )})";
                    }

                    return trim( "{$join->type} join {$table} " . $this->compile_ons( $join ) );
                }, $joins 
            ) 
        );
    }

    /**
     * Compile the "ons" portion of a join clause.
     *
     * @param  JoinClause  $query
     * @return string
     */
    public function compile_ons( JoinClause $query ) {
        if ( empty( $query->get_ons() ) ) {
            return '';
        }
        return $this->compile_where_or_having( $query, $query->get_ons(), 'on' );
    }

    /**
     * Shared logic for compiling WHERE, HAVING, and ON clauses.
     *
     * @param  Builder  $query
     * @param  array   $items
     * @param  string  $type  ('where'|'having'|'on')
     * @return string
     */
    protected function compile_where_or_having( Builder $query, array $items, string $type = 'where' ) {
        $sql          = [];
        $binding_type = ( $type === 'on' ) ? 'join' : $type;

        foreach ( $items as $where ) {
            $sql[] = $this->{"compile_where_{$where['type']}"}( $query, $where, $binding_type, $type );
        }

        $where_query = trim( implode( ' ', $sql ) );
        $where_query = preg_replace( '/and |or /i', '', $where_query, 1 );

        if ( empty( $where_query ) || in_array( strtolower( $where_query ), ['where', 'having', 'on'] ) ) {
            return '';
        }

        return $type . ' ' . $where_query;
    }

    protected function compile_where_basic( Builder $query, $where, $binding_type ) {
        $column = $where['column'];
        if ( $column instanceof Builder ) {
            $sql = $column->get_raw_sql();
            $query->merge_bindings( $column, $binding_type );
            $column = "($sql)";
        } else {
            $column = $this->wrap( $column );
        }

        $value = $where['value'];
        if ( $value instanceof Builder ) {
            $sql = $value->get_raw_sql();
            $query->merge_bindings( $value, $binding_type );
            $value_sql = "($sql)";
        } else {
            $value_sql = $query->set_binding( $value, $binding_type );
        }

        $prefix = $where['not'] ? "{$where['boolean']} not" : $where['boolean'];

        return "{$prefix} {$column} {$where['operator']} {$value_sql}";
    }

    protected function compile_where_like( Builder $query, $where, $binding_type ) {
        $like = $where['not'] ? 'not like' : 'like';
        return "{$where['boolean']} " . $this->wrap( $where['column'] ) . " {$like} {$query->set_binding($where['value'], $binding_type)}";
    }

    protected function compile_where_between( Builder $query, $where, $binding_type ) {
        $between = $where['not'] ? 'not between' : 'between';
        return "{$where['boolean']} " . $this->wrap( $where['column'] ) . " {$between} {$query->set_binding($where['values'][0], $binding_type)} and {$query->set_binding($where['values'][1], $binding_type)}";
    }

    protected function compile_where_in( Builder $query, $where, $binding_type ) {
        $in = $where['not'] ? 'not in' : 'in';

        if ( $where['values'] instanceof Builder ) {
            $sql = $where['values']->get_raw_sql();
            $query->merge_bindings( $where['values'], $binding_type );
            return "{$where['boolean']} " . $this->wrap( $where['column'] ) . " {$in} ({$sql})";
        }

        $values = implode(
            ', ', array_map(
                function( $value ) use( $query, $binding_type ) {
                    return $query->set_binding( $value, $binding_type );
                }, $where['values'] 
            ) 
        );

        return "{$where['boolean']} " . $this->wrap( $where['column'] ) . " {$in} ({$values})";
    }

    protected function compile_where_column( Builder $query, $where ) {
        if ( is_null( $where['value'] ) ) {
            return "{$where['boolean']} " . $this->wrap( $where['column'] );
        }
        return "{$where['boolean']} " . $this->wrap( $where['column'] ) . " {$where['operator']} " . $this->wrap( $where['value'] );
    }

    protected function compile_where_exists( Builder $query, $where ) {
        $exists_query = $where['query'];
        $sql          = $exists_query->get_raw_sql();
        $query->merge_bindings( $exists_query, 'where' );

        $exists = $where['not'] ? 'not exists' : 'exists';
        return "{$where['boolean']} {$exists} ({$sql})";
    }

    protected function compile_where_raw( Builder $query, $where, $binding_type ) {
        if ( ! empty( $where['bindings'] ) ) {
            $query->add_binding( $where['bindings'], $binding_type );
        }
        return "{$where['boolean']} {$where['sql']}";
    }

    protected function compile_where_is_null( Builder $query, $where ) {
        $null = $where['not'] ? "not null" : "null";
        return "{$where['boolean']} " . $this->wrap( $where['column'] ) . " is {$null}";
    }

    protected function compile_where_nested( Builder $query, $where, $binding_type, $type ) {
        $nested_query = $where['query'];
        $method       = 'get_' . $type . 's';
        $items        = $nested_query->$method();

        if ( empty( $items ) ) {
            return '';
        }

        $sql = ltrim( $this->compile_where_or_having( $nested_query, $items, $type ), $type );
        $query->merge_bindings( $nested_query, $binding_type );

        $prefix = $where['not'] ? "{$where['boolean']} not" : $where['boolean'];

        return "{$prefix} ({$sql} )";
    }

    public function compile_groups( Builder $query, $groups ) {
        return 'group by ' . implode( ', ', array_map( [$this, 'wrap'], $groups ) );
    }

    protected function compile_havings( Builder $query ) {
        if ( empty( $query->get_havings() ) ) {
            return '';
        }
        return $this->compile_where_or_having( $query, $query->get_havings(), 'having' );
    }

    protected function compile_orders( Builder $query, $orders ) {
        if ( empty( $orders ) ) {
            return '';
        }

        return 'order by ' . implode(
            ', ', array_map(
                function( $order ) {
                    return $this->wrap( $order['column'] ) . ' ' . $order['direction'];
                }, $orders 
            ) 
        );
    }

    protected function compile_limit( Builder $query, $limit ) {
        return 'limit ' . $query->set_binding( (int) $limit, 'limit' );
    }

    protected function compile_offset( Builder $query, $offset ) {
        return 'offset ' . $query->set_binding( (int) $offset, 'offset' );
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    public function wrap( $value ) {
        if ( is_null( $value ) || $value === '*' ) {
            return $value;
        }

        if ( strpos( strtolower( $value ), ' as ' ) !== false ) {
            $parts = preg_split( '/\s+as\s+/i', $value );
            return $this->wrap( $parts[0] ) . ' as ' . $this->wrap( $parts[count( $parts ) - 1] );
        }

        if ( strpos( $value, '(' ) !== false ) {
            return $value;
        }

        if ( strpos( $value, '.' ) !== false ) {
            return implode( '.', array_map( [$this, 'wrap'], explode( '.', $value ) ) );
        }

        return '`' . str_replace( '`', '``', $value ) . '`';
    }

    /**
     * Concatenate an array of segments, removing empty ones.
     *
     * @param  array  $segments
     * @return string
     */
    protected function concatenate( $segments ) {
        return implode(
            ' ', array_filter(
                $segments, function ( $value ) {
                    return (string) $value !== '';
                } 
            ) 
        );
    }

    /**
     * Compile the "unions" portion of the query.
     *
     * @param  Builder  $query
     * @param  array  $unions
     * @return string
     */
    protected function compile_unions( Builder $query, $unions ) {
        $sql = '';

        foreach ( $unions as $union ) {
            $joiner = $union['all'] ? ' union all ' : ' union ';
            // Reset the subquery bindings so each compile is fresh, then merge into parent
            $union['query']->reset_bindings();
            $sql .= $joiner . $union['query']->compile_sql();
            $query->merge_bindings( $union['query'], 'union' );
        }

        return $sql;
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  Builder  $query
     * @param  array  $columns
     * @return string
     */
    public function columnize( Builder $query, array $columns ) {
        return implode(
            ', ', array_map(
                function( $column, $key ) use ( $query ) {
                    $alias = is_string( $key ) ? " as " . $this->wrap( $key ) : "";

                    if ( $column instanceof Builder ) {
                          $sql = $column->get_raw_sql();
                          $query->merge_bindings( $column, 'select' );
                          return "($sql)$alias";
                    }

                    return ( $column === '*' ? $column : $this->wrap( $column ) ) . $alias;
                }, $columns, array_keys( $columns ) 
            ) 
        );
    }
}

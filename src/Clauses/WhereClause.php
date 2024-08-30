<?php

namespace WpMVC\Database\Clauses;

defined( "ABSPATH" ) || exit;

trait WhereClause {
    use Clause;

    /**
     * Get the array of where clauses.
     *
     * @return array The array of where clauses.
     */
    public function get_wheres(): array {
        return $this->clauses['wheres'] ?? [];
    }

    /**
     * Unset a where from the query.
     *
     * @param int|string $key The key or index of the clause to remove.
     * @return $this
     */
    public function unset_where( $key ) {
        return $this->unset_clause( 'wheres', $key );
    }

    /**
     * Add a basic where to the query.
     *
     * @param (Closure(static): mixed)|string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where( $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        return $this->clause( 'wheres', $column, $operator, $value, $boolean, $name );
    }

    /**
     * Add an "or where" to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where( string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause( 'wheres', $column, $operator, $value, $name );
    }

    /**
     * Add a where comparing two columns to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_column( string $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        return $this->where_column( 'wheres', $column, $operator, $value, $boolean, $name );
    }

    /**
     * Add an "or where comparing two columns" to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_column( string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause_column( 'wheres', $column, $operator, $value, $name );
    }

    /**
     * Add an exists where to the query.
     *
     * @param Closure|array|static $callback The query or callback for the exists where.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the exists where.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_exists( $callback, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_exists( 'wheres', $callback, $boolean, $not, $name );
    }

    /**
     * Add a "not exists" where to the query.
     *
     * @param Closure|array|static $callback The query or callback for the not exists where.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_not_exists( $callback, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_exists( 'wheres', $callback, $boolean, $name );
    }

    /**
     * Add a "where in" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the in where.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_in( string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_in( 'wheres', $column, $values, $boolean, $not, $name );
    }

    /**
     * Add an "or where in" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_in( 'wheres', $column, $values, $name );
    }

    /**
     * Add a "where not in" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_not_in( string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_in( 'wheres', $column, $values, $boolean, $name );
    }

    /**
     * Add an "or where not in" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_not_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_in( 'wheres', $column, $values, $name );
    }

    /**
     * Add a "where is null" where to the query.
     *
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null where.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_is_null( string $column, bool $not = false, $boolean = 'and', ?string $name = null ) {
        return $this->clause_is_null( 'wheres', $column, $not, $boolean, $name );
    }

    /**
     * Add an "or where is null" where to the query.
     *
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null where.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_is_null( string $column, bool $not = false, ?string $name = null ) {
        return $this->or_clause_is_null( 'wheres', $column, $not, $name );
    }

    /**
     * Add a "where not is null" where to the query.
     *
     * @param string $column The column to check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_not_is_null( string $column, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_is_null( 'wheres', $column, $boolean, $name );
    }

    /**
     * Add an "or where not is null" where to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_not_is_null( string $column, ?string $name = null ) {
        return $this->or_clause_not_is_null( 'wheres', $column, $name );
    }

    /**
     * Add a "where between" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the between where.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_between( string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_between( 'wheres', $column, $values, $boolean, $not, $name );
    }

    /**
     * Add an "or where between" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_between( 'wheres', $column, $values, $name );
    }

    /**
     * Add a "where not between" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_not_between( string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_between( 'wheres', $column, $values, $boolean, $name );
    }

    /**
     * Add an "or where not between" where to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_not_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_between( 'wheres', $column, $values, $name );
    }

    /**
     * Add a raw where to the query.
     *
     * @param string $sql The raw SQL where.
     * @param array $bindings The bindings for the raw SQL.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function where_raw( string $sql, array $bindings = [], $boolean = 'and', ?string $name = null ) {
        return $this->clause_raw( 'wheres', $sql, $bindings, $boolean, $name );
    }

    /**
     * Add an "or raw where" to the query.
     *
     * @param string $sql The raw SQL where.
     * @param array $bindings The bindings for the raw SQL.
     * @param ?string $name Optional name for the where.
     * @return $this
     */
    public function or_where_raw( string $sql, array $bindings = [], ?string $name = null ) {
        return $this->or_clause_raw( 'wheres', $sql, $bindings, $name );
    }
}

<?php

namespace WpMVC\Database\Clauses;

defined( "ABSPATH" ) || exit;

trait OnClause {
    use Clause;

    /**
     * Get the array of on clauses.
     *
     * @return array The array of on clauses.
     */
    public function get_ons(): array {
        return $this->clauses['ons'] ?? [];
    }

    /**
     * Unset a on from the query.
     *
     * @param int|string $key The key or index of the clause to remove.
     * @return $this
     */
    public function unset_on( $key ) {
        return $this->unset_clause( 'ons', $key );
    }

    /**
     * Add a basic on to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on( string $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        return $this->clause( 'ons', $column, $operator, $value, $boolean, $name );
    }

    /**
     * Add an "or on" to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on( string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause( 'ons', $column, $operator, $value, $name );
    }

    /**
     * Add a on comparing two columns to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_column( string $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        return $this->clause_column( 'ons', $column, $operator, $value, $boolean, $name );
    }

    /**
     * Add an "or on comparing two columns" to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_column( string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause_column( 'ons', $column, $operator, $value, $name );
    }

    /**
     * Add an exists on to the query.
     *
     * @param Closure|array|static $callback The query or callback for the exists on.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the exists on.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_exists( $callback, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_exists( 'ons', $callback, $boolean, $not, $name );
    }

    /**
     * Add a "not exists" on to the query.
     *
     * @param Closure|array|static $callback The query or callback for the not exists on.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_not_exists( $callback, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_exists( 'ons', $callback, $boolean, $name );
    }

    /**
     * Add a "on in" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the in on.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_in( string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_in( 'ons', $column, $values, $boolean, $not, $name );
    }

    /**
     * Add an "or on in" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_in( 'ons', $column, $values, $name );
    }

    /**
     * Add a "on not in" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_not_in( string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_in( 'ons', $column, $values, $boolean, $name );
    }

    /**
     * Add an "or on not in" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_not_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_in( 'ons', $column, $values, $name );
    }

    /**
     * Add a "on is null" on to the query.
     *
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null on.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_is_null( string $column, bool $not = false, $boolean = 'and', ?string $name = null ) {
        return $this->clause_is_null( 'ons', $column, $not, $boolean, $name );
    }

    /**
     * Add an "or on is null" on to the query.
     *
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null on.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_is_null( string $column, bool $not = false, ?string $name = null ) {
        return $this->or_clause_is_null( 'ons', $column, $not, $name );
    }

    /**
     * Add a "on not is null" on to the query.
     *
     * @param string $column The column to check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_not_is_null( string $column, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_is_null( 'ons', $column, $boolean, $name );
    }

    /**
     * Add an "or on not is null" on to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_not_is_null( string $column, ?string $name = null ) {
        return $this->or_clause_not_is_null( 'ons', $column, $name );
    }

    /**
     * Add a "on between" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the between on.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_between( string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_between( 'ons', $column, $values, $boolean, $not, $name );
    }

    /**
     * Add an "or on between" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_between( 'ons', $column, $values, $name );
    }

    /**
     * Add a "on not between" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_not_between( string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_between( 'ons', $column, $values, $boolean, $name );
    }

    /**
     * Add an "or on not between" on to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_not_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_between( 'ons', $column, $values, $name );
    }

    /**
     * Add a raw on to the query.
     *
     * @param string $sql The raw SQL on.
     * @param array $bindings The bindings for the raw SQL.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function on_raw( string $sql, array $bindings = [], $boolean = 'and', ?string $name = null ) {
        return $this->clause_raw( 'ons', $sql, $bindings, $boolean, $name );
    }

    /**
     * Add an "or raw on" to the query.
     *
     * @param string $sql The raw SQL on.
     * @param array $bindings The bindings for the raw SQL.
     * @param ?string $name Optional name for the on.
     * @return $this
     */
    public function or_on_raw( string $sql, array $bindings = [], ?string $name = null ) {
        return $this->or_clause_raw( 'ons', $sql, $bindings, $name );
    }
}

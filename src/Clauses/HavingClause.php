<?php

namespace WpMVC\Database\Clauses;

defined( "ABSPATH" ) || exit;

trait HavingClause {
    use Clause;

    /**
     * Get the array of having clauses.
     *
     * @return array The array of having clauses.
     */
    public function get_havings(): array {
        return $this->clauses['havings'] ?? [];
    }

    /**
     * Unset a having from the query.
     *
     * @param int|string $key The key or index of the clause to remove.
     * @return $this
     */
    public function unset_having( $key ) {
        return $this->unset_clause( 'havings', $key );
    }

    /**
     * Add a basic having to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having( string $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        return $this->clause( 'havings', $column, $operator, $value, $boolean, $name );
    }

    /**
     * Add an "or having" to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having( string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause( 'havings', $column, $operator, $value, $name );
    }

    /**
     * Add a having comparing two columns to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_column( string $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        return $this->having_column( 'havings', $column, $operator, $value, $boolean, $name );
    }

    /**
     * Add an "or having comparing two columns" to the query.
     *
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_column( string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause_column( 'havings', $column, $operator, $value, $name );
    }

    /**
     * Add an exists having to the query.
     *
     * @param Closure|array|static $callback The query or callback for the exists having.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the exists having.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_exists( $callback, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_exists( 'havings', $callback, $boolean, $not, $name );
    }

    /**
     * Add a "not exists" having to the query.
     *
     * @param Closure|array|static $callback The query or callback for the not exists having.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_not_exists( $callback, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_exists( 'havings', $callback, $boolean, $name );
    }

    /**
     * Add a "having in" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the in having.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_in( string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_in( 'havings', $column, $values, $boolean, $not, $name );
    }

    /**
     * Add an "or having in" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_in( 'havings', $column, $values, $name );
    }

    /**
     * Add a "having not in" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_not_in( string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_in( 'havings', $column, $values, $boolean, $name );
    }

    /**
     * Add an "or having not in" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_not_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_in( 'havings', $column, $values, $name );
    }

    /**
     * Add a "having is null" having to the query.
     *
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null having.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_is_null( string $column, bool $not = false, $boolean = 'and', ?string $name = null ) {
        return $this->clause_is_null( 'havings', $column, $not, $boolean, $name );
    }

    /**
     * Add an "or having is null" having to the query.
     *
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null having.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_is_null( string $column, bool $not = false, ?string $name = null ) {
        return $this->or_clause_is_null( 'havings', $column, $not, $name );
    }

    /**
     * Add a "having not is null" having to the query.
     *
     * @param string $column The column to check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_not_is_null( string $column, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_is_null( 'havings', $column, $boolean, $name );
    }

    /**
     * Add an "or having not is null" having to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_not_is_null( string $column, ?string $name = null ) {
        return $this->or_clause_not_is_null( 'havings', $column, $name );
    }

    /**
     * Add a "having between" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the between having.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_between( string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        return $this->clause_between( 'havings', $column, $values, $boolean, $not, $name );
    }

    /**
     * Add an "or having between" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_between( 'havings', $column, $values, $name );
    }

    /**
     * Add a "having not between" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_not_between( string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_not_between( 'havings', $column, $values, $boolean, $name );
    }

    /**
     * Add an "or having not between" having to the query.
     *
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_not_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_between( 'havings', $column, $values, $name );
    }

    /**
     * Add a raw having to the query.
     *
     * @param string $sql The raw SQL having.
     * @param array $bindings The bindings for the raw SQL.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function having_raw( string $sql, array $bindings = [], $boolean = 'and', ?string $name = null ) {
        return $this->clause_raw( 'havings', $sql, $bindings, $boolean, $name );
    }

    /**
     * Add an "or raw having" to the query.
     *
     * @param string $sql The raw SQL having.
     * @param array $bindings The bindings for the raw SQL.
     * @param ?string $name Optional name for the having.
     * @return $this
     */
    public function or_having_raw( string $sql, array $bindings = [], ?string $name = null ) {
        return $this->or_clause_raw( 'havings', $sql, $bindings, $name );
    }
}

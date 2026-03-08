<?php
/**
 * Join On clause handling trait.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Clauses;

defined( "ABSPATH" ) || exit;

use Closure;

/**
 * Trait OnClause
 *
 * Provides methods for adding JOIN ON clauses to the join builder.
 *
 * @package WpMVC\Database\Clauses
 */
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
     * @param int|string $key The key or index of the on to remove.
     * @return static
     */
    public function unset_on( $key ) {
        return $this->unset_clause( 'ons', $key );
    }

    /**
     * Add a basic "on" clause to the join.
     *
     * In WpMVC (Laravel parity), on() defaults to comparing two columns.
     * If you need to compare a column to a value, use where() on the JoinClause.
     *
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $boolean
     * @return static
     */
    public function on( $first, $operator = null, $second = null, $boolean = 'and' ) {
        if ( $first instanceof Closure ) {
            return $this->clause( "ons", $first, $operator, $second, null, $boolean );
        }

        return $this->on_column( $first, $operator, $second, $boolean );
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @return static
     */
    public function or_on( $first, $operator = null, $second = null ) {
        return $this->on( $first, $operator, $second, 'or' );
    }

    /**
     * Add an "on not" to the query.
     *
     * @param (Closure(static): mixed)|static|string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_not( $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->clause_not( "ons", $column, $operator, $value, $name );
    }

    /**
     * Add an "or on not" to the query.
     *
     * @param (Closure(static): mixed)|static|string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_not( $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->or_clause_not( "ons", $column, $operator, $value, $name );
    }

    /**
     * Add a on comparing two columns to the query.
     *
     * @param string $first_column The first column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $second_column The second column to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_column( string $first_column, $operator = null, $second_column = null, $boolean = 'and' ) {
        return $this->clause_column( "ons", $first_column, $operator, $second_column, null, $boolean );
    }

    /**
     * Add an "or on comparing two columns" to the query.
     * 
     * @param string $first_column The first column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $second_column The second column to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_column( string $first_column, $operator = null, $second_column = null ) {
        return $this->on_column( $first_column, $operator, $second_column, 'or' );
    }

    /**
     * Add an exists on to the query.
     *
     * @param (Closure(static): mixed)|static $callback The query or callback for the exists on.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_exists( $callback, ?string $name = null ) {
        return $this->clause_exists( "ons", $callback, $name );
    }

    /**
     * Add an "or exists" on to the query.
     *
     * @param (Closure(static): mixed)|static $callback The query or callback for the exists on.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_exists( $callback, ?string $name = null ) {
        return $this->or_clause_exists( "ons", $callback, $name );
    }

    /**
     * Add a "not exists" on to the query.
     *
     * @param (Closure(static): mixed)|static $callback The query or callback for the exists on.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_not_exists( $callback, ?string $name = null ) {
        return $this->clause_not_exists( "ons", $callback, $name );
    }

    /**
     * Add an "or not exists" on to the query.
     *
     * @param (Closure(static): mixed)|static $callback The query or callback for the exists on.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_not_exists( $callback, ?string $name = null ) {
        return $this->or_clause_not_exists( "ons", $callback, $name );
    }

    /**
     * Add a "on in" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to check against.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_in( string $column, array $values, ?string $name = null ) {
        return $this->clause_in( "ons", $column, $values, $name );
    }

    /**
     * Add an "or in" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to check against.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_in( "ons", $column, $values, $name );
    }

    /**
     * Add a "not in" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to check against.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_not_in( string $column, array $values, ?string $name = null ) {
        return $this->clause_not_in( "ons", $column, $values, $name );
    }

    /**
     * Add an "or not in" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to check against.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_not_in( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_in( "ons", $column, $values, $name );
    }

    /**
     * Add a "like" on to the query.
     *
     * @param string $column The column to compare.
     * @param string $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_like( string $column, string $value, ?string $name = null ) {
        return $this->clause_like( "ons", $column, $value, $name );
    }

    /**
     * Add an "or like" on to the query.
     *
     * @param string $column The column to compare.
     * @param string $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_like( string $column, string $value, ?string $name = null ) {
        return $this->or_clause_like( "ons", $column, $value, $name );
    }

    /**
     * Add a "not like" on to the query.
     *
     * @param string $column The column to compare.
     * @param string $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_not_like( string $column, string $value, ?string $name = null ) {
        return $this->clause_not_like( "ons", $column, $value, $name );
    }

    /**
     * Add an "or not like" on to the query.
     *
     * @param string $column The column to compare.
     * @param string $value The value to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_not_like( string $column, string $value, ?string $name = null ) {
        return $this->or_clause_not_like( "ons", $column, $value, $name );
    }

    /**
     * Add an "null" on to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_null( string $column, ?string $name = null ) {
        return $this->clause_null( "ons", $column, $name );
    }

    /**
     * Add an "or null" on to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_null( string $column, ?string $name = null ) {
        return $this->or_clause_null( "ons", $column, $name );
    }

    /**
     * Add a "not null" on to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_not_null( string $column, ?string $name = null ) {
        return $this->clause_not_null( "ons", $column, $name );
    }

    /**
     * Add an "or not null" on to the query.
     *
     * @param string $column The column to check.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_not_null( string $column, ?string $name = null ) {
        return $this->or_clause_not_null( "ons", $column, $name );
    }

    /**
     * Add a "between" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_between( string $column, array $values, ?string $name = null ) {
        return $this->clause_between( "ons", $column, $values, $name );
    }

    /**
     * Add an "or between" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_between( "ons", $column, $values, $name );
    }

    /**
     * Add a "not between" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_not_between( string $column, array $values, ?string $name = null ) {
        return $this->clause_not_between( "ons", $column, $values, $name );
    }

    /**
     * Add an "or not between" on to the query.
     *
     * @param string $column The column to compare.
     * @param array $values The values to compare.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_not_between( string $column, array $values, ?string $name = null ) {
        return $this->or_clause_not_between( "ons", $column, $values, $name );
    }

    /**
     * Add a raw on to the query.
     *
     * @param string $sql The SQL statement.
     * @param array $bindings The bindings for the raw SQL statement.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function on_raw( string $sql, array $bindings = [], ?string $name = null ) {
        return $this->clause_raw( "ons", $sql, $bindings, $name );
    }

    /**
     * Add an "or raw" on to the query.
     *
     * @param string $sql The SQL statement.
     * @param array $bindings The bindings for the raw SQL statement.
     * @param ?string $name Optional name for the on.
     * @return static
     */
    public function or_on_raw( string $sql, array $bindings = [], ?string $name = null ) {
        return $this->or_clause_raw( "ons", $sql, $bindings, $name );
    }
}

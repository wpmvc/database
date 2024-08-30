<?php

namespace WpMVC\Database\Clauses;

defined( "ABSPATH" ) || exit;

use Closure;

trait Clause {
    protected array $clauses = [];

    public function get_clauses() {
        return $this->clauses;
    }

    /**
     * Set a clause in the query.
     *
     * @param string $clause_type The type of the clause.
     * @param array $args The arguments for the clause.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function set_clause( string $clause_type, array $args, ?string $name = null ): self {
        if ( $name ) {
            $this->clauses[$clause_type][$name] = $args;
        } else {
            $this->clauses[$clause_type][] = $args;
        }
        return $this;
    }

    /**
     * Unset a clause from the query.
     *
     * @param string $clauses The type of clause (e.g., 'wheres', 'havings', 'ons') to unset.
     * @param int|string $key The key or index of the clause to remove.
     * @return $this Returns the current instance for method chaining.
     */
    protected function unset_clause( string $clauses, $key ) {
        unset( $this->clauses[$clauses][$key] );
        return $this;
    }

    /**
     * Add a basic clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param (Closure(static): mixed)|string $column
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause( string $clause_type, $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        if ( $column instanceof Closure ) {
            $type = 'nested';

            $query = new static( $this->model );

            if ( is_callable( $column ) ) {   
                call_user_func( $column, $query );
            }

            $data = compact( 'type', 'boolean', 'query', 'name' );

        } else {
            // Prepare value and operator for the clause
            [$value, $operator] = $this->prepare_value_and_operator( $value, $operator, func_num_args() === 2 );

            // If the operator is invalid, default to '='
            if ( $this->invalid_operator( $operator ) ) {
                [$value, $operator] = [$operator, '='];
            }

            $type = 'basic'; // Define the type of the clause
            $data = compact( 'type', 'boolean', 'column', 'operator', 'value' );
        }
    
        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or clause" to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause( string $clause_type, string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->clause( $clause_type, $column, $operator, $value, 'or', $name );
    }

    /**
     * Add a clause comparing two columns to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_column( string $clause_type, string $column, $operator = null, $value = null, $boolean = 'and', ?string $name = null ) {
        // Prepare value and operator for the clause
        [$value, $operator] = $this->prepare_value_and_operator( $value, $operator, func_num_args() === 2 );

        // If the operator is invalid, default to '='
        if ( $this->invalid_operator( $operator ) ) {
            [$value, $operator] = [$operator, '='];
        }

        $type = 'column'; // Define the type of the clause
        $data = compact( 'type', 'boolean', 'column', 'operator', 'value' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or clause comparing two columns" to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to compare.
     * @param mixed $operator The operator for comparison.
     * @param mixed $value The value to compare.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_column( string $clause_type, string $column, $operator = null, $value = null, ?string $name = null ) {
        return $this->clause_column( $clause_type, $column, $operator, $value, 'or', $name );
    }

    /**
     * Add an exists clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param Closure|array|static $callback The query or callback for the exists clause.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the exists clause.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_exists( string $clause_type, $callback, $boolean = 'and', $not = false, ?string $name = null ) {
        $query = is_callable( $callback ) ? ( function() use ( $callback ) {
            $query = new static( $this->model );
            call_user_func( $callback, $query );
            return $query;
        } )() : $callback;

        $type = 'exists'; // Define the type of the clause
        $data = compact( 'type', 'query', 'boolean', 'not' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add a "not exists" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param Closure|array|static $callback The query or callback for the not exists clause.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_not_exists( string $clause_type, $callback, $boolean = 'and', ?string $name = null ) {
        return $this->clause_exists( $clause_type, $callback, $boolean, true, $name );
    }

    /**
     * Add a "clause in" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the in clause.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_in( string $clause_type, string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        $type = 'in'; // Define the type of the clause
        $data = compact( 'type', 'column', 'values', 'boolean', 'not' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or clause in" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The values for the "in" check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_in( string $clause_type, string $column, array $values, ?string $name = null ) {
        return $this->clause_in( $clause_type, $column, $values, 'or', false, $name );
    }

    /**
     * Add a "clause not in" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_not_in( string $clause_type, string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_in( $clause_type, $column, $values, $boolean, true, $name );
    }

    /**
     * Add an "or clause not in" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The values for the "not in" check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_not_in( string $clause_type, string $column, array $values, ?string $name = null ) {
        return $this->clause_not_in( $clause_type, $column, $values, 'or', $name );
    }

    /**
     * Add a "clause like" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param string $value The value for the "like" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the like clause.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_like( string $clause_type, string $column, string $value, $boolean = 'and', $not = false, ?string $name = null ) {
        $type = 'like'; // Define the type of the clause
        $data = compact( 'type', 'column', 'value', 'boolean', 'not' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or clause like" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param string $value The value for the "like" check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_like( string $clause_type, string $column, string $value, ?string $name = null ) {
        return $this->clause_like( $clause_type, $column, $value, 'or', false, $name );
    }

    /**
     * Add a "clause not like" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param string $value The value for the "not like" check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_not_like( string $clause_type, string $column, string $value, $boolean = 'and', ?string $name = null ) {
        return $this->clause_like( $clause_type, $column, $value, $boolean, true, $name );
    }

    /**
     * Add an "or clause not like" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param string $value The value for the "not like" check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_not_like( string $clause_type, string $column, string $value, ?string $name = null ) {
        return $this->clause_not_like( $clause_type, $column, $value, 'or', $name );
    }

    /**
     * Add a "clause is null" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null clause.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_is_null( string $clause_type, string $column, bool $not = false, $boolean = 'and', ?string $name = null ) {
        $type = 'is_null'; // Define the type of the clause
        $data = compact( 'type', 'column', 'boolean', 'not' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or clause is null" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param bool $not Whether to negate the is null clause.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_is_null( string $clause_type, string $column, bool $not = false, ?string $name = null ) {
        return $this->clause_is_null( $clause_type, $column, $not, 'or', $name );
    }

    /**
     * Add a "clause not is null" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_not_is_null( string $clause_type, string $column, $boolean = 'and', ?string $name = null ) {
        return $this->clause_is_null( $clause_type, $column, true, $boolean, $name );
    }

    /**
     * Add an "or clause not is null" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_not_is_null( string $clause_type, string $column, ?string $name = null ) {
        return $this->or_clause_is_null( $clause_type, $column, true, $name );
    }

    /**
     * Add a "clause between" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param bool $not Whether to negate the between clause.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_between( string $clause_type, string $column, array $values, $boolean = 'and', $not = false, ?string $name = null ) {
        $type = 'between'; // Define the type of the clause
        $data = compact( 'type', 'column', 'values', 'boolean', 'not' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or clause between" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The range of values for the between check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_between( string $clause_type, string $column, array $values, ?string $name = null ) {
        return $this->clause_between( $clause_type, $column, $values, 'or', false, $name );
    }

    /**
     * Add a "clause not between" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_not_between( string $clause_type, string $column, array $values, $boolean = 'and', ?string $name = null ) {
        return $this->clause_between( $clause_type, $column, $values, $boolean, true, $name );
    }

    /**
     * Add an "or clause not between" clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $column The column to check.
     * @param array $values The range of values for the not between check.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_not_between( string $clause_type, string $column, array $values, ?string $name = null ) {
        return $this->clause_not_between( $clause_type, $column, $values, 'or', $name );
    }

    /**
     * Add a raw clause to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $sql The raw SQL clause.
     * @param array $bindings The bindings for the raw SQL.
     * @param string $boolean The boolean operator ('and' or 'or').
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function clause_raw( string $clause_type, string $sql, array $bindings = [], $boolean = 'and', ?string $name = null ) {
        $type = 'raw'; // Define the type of the clause
        $data = compact( 'type', 'sql', 'bindings', 'boolean' );

        return $this->set_clause( $clause_type, $data, $name );
    }

    /**
     * Add an "or raw clause" to the query.
     *
     * @param string $clause_type The type of the clause.
     * @param string $sql The raw SQL clause.
     * @param array $bindings The bindings for the raw SQL.
     * @param ?string $name Optional name for the clause.
     * @return $this
     */
    protected function or_clause_raw( string $clause_type, string $sql, array $bindings = [], ?string $name = null ) {
        return $this->clause_raw( $clause_type, $sql, $bindings, 'or', $name );
    }
}

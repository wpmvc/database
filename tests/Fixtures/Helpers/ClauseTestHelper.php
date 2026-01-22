<?php

namespace WpMVC\Database\Tests\Fixtures\Helpers;

/**
 * Abstract helper class for testing clause traits.
 * Provides common methods needed by clause traits (WhereClause, HavingClause, OnClause).
 */
abstract class ClauseTestHelper {
    /**
     * List of valid operators.
     *
     * @var array
     */
    public $operators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like'];

    /**
     * Prepare value and operator for clause.
     *
     * @param mixed $value
     * @param mixed $operator
     * @param bool $use_default
     * @return array
     */
    protected function prepare_value_and_operator( $value, $operator, $use_default = false ) {
        if ( $use_default ) {
            return [$operator, '='];
        }
        return [$value, $operator];
    }

    /**
     * Check if operator is invalid.
     *
     * @param mixed $operator
     * @return bool
     */
    protected function invalid_operator( $operator ) {
        return ! in_array( $operator, $this->operators );
    }
}

<?php
/**
 * Join clause class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Query;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Clauses\OnClause;
use WpMVC\Database\Eloquent\Model;

/**
 * Class JoinClause
 *
 * Represents a JOIN clause in a query builder.
 *
 * @package WpMVC\Database\Query
 */
class JoinClause extends Builder {

    use OnClause;

    /**
     * The type of join being performed.
     *
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
     *
     * @var string
     */
    public $table;

    /**
     * Create a new join clause instance.
     *
     * @param  string  $table
     * @param  string  $type
     * @param  Model   $model
     * @return void
     */
    public function __construct( string $table, string $type, Model $model ) {
        parent::__construct( $model );
        $parts = preg_split( '/\s+as\s+/i', $table );
        $this->from( $parts[0], isset( $parts[1] ) ? $parts[1] : null );
        $this->type =  $type;
    }
}
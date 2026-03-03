<?php
/**
 * MorphOne relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Query\Builder;
use WpMVC\Database\Eloquent\Model;

/**
 * Class MorphOne
 *
 * Defines a polymorphic one-to-one relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class MorphOne extends HasOne {
    /**
     * The morph name of the relationship.
     *
     * @var string
     */
    public $morph_name;

    /**
     * The type column for the polymorphic relationship.
     *
     * @var string
     */
    public $type_column;

    /**
     * The ID column for the polymorphic relationship.
     *
     * @var string
     */
    public $id_column;

    /**
     * Create a new morph one relation instance.
     *
     * @param  Builder  $query
     * @param  Model    $parent
     * @param  string   $morph_name
     * @param  string   $type_column
     * @param  string   $id_column
     * @param  string   $local_key
     */
    public function __construct( Builder $query, Model $parent, $morph_name, $type_column, $id_column, $local_key ) {
        $this->morph_name  = $morph_name;
        $this->type_column = $type_column;
        $this->id_column   = $id_column;

        parent::__construct( $query, $parent, $id_column, $local_key );
    }

    /**
     * Set the constraints for an individual relationship query.
     *
     * @return void
     */
    protected function add_constraints() {
        parent::add_constraints();

        $this->where( $this->type_column, '=', $this->parent->get_morph_class() );
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    protected function add_eager_constraints( array $models ) {
        parent::add_eager_constraints( $models );

        $this->where( $this->type_column, '=', $this->parent->get_morph_class() );
    }

    protected function add_existence_constraints() {
        $this->where( $this->type_column, '=', $this->parent->get_morph_class() );
    }
}

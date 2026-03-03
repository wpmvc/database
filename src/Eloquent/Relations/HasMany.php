<?php
/**
 * HasMany relationship class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Relations;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Eloquent\Collection;

/**
 * Class HasMany
 *
 * Defines a one-to-many relationship.
 *
 * @package WpMVC\Database\Eloquent\Relations
 */
class HasMany extends HasOneOrMany {
    /**
     * Get the default value for the relationship.
     *
     * @return Collection
     */
    protected function get_default_value() {
        return $this->related->new_collection();
    }
}
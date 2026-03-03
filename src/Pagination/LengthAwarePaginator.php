<?php
/**
 * Length aware paginator class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Pagination;

defined( "ABSPATH" ) || exit;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use WpMVC\Database\Eloquent\Collection;

/**
 * Class LengthAwarePaginator
 *
 * Provides pagination functionality with total count awareness.
 *
 * @package WpMVC\Database\Pagination
 */
class LengthAwarePaginator implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {
    /**
     * The items for the current page.
     *
     * @var Collection|array
     */
    protected $items;

    /**
     * The total number of items before pagination.
     *
     * @var int
     */
    protected $total;

    /**
     * The number of items to be shown per page.
     *
     * @var int
     */
    protected $per_page;

    /**
     * The current page being viewed.
     *
     * @var int
     */
    protected $current_page;

    /**
     * Create a new paginator instance.
     *
     * @param  mixed  $items
     * @param  int  $total
     * @param  int  $per_page
     * @param  int|null  $current_page
     */
    public function __construct( $items, $total, $per_page, $current_page = null ) {
        $this->items        = $items instanceof Collection ? $items : new Collection( $items );
        $this->total        = (int) $total;
        $this->per_page     = (int) $per_page;
        $this->current_page = $current_page ?: 1;
    }

    /**
     * Get the total number of items before pagination.
     *
     * @return int
     */
    public function total() {
        return $this->total;
    }

    /**
     * Get the number of items shown per page.
     *
     * @return int
     */
    public function per_page() {
        return $this->per_page;
    }

    /**
     * Get the current page.
     *
     * @return int
     */
    public function current_page() {
        return $this->current_page;
    }

    /**
     * Get the last page number.
     *
     * @return int
     */
    public function last_page() {
        return max( (int) ceil( $this->total / $this->per_page ), 1 );
    }

    /**
     * Get the items for the current page.
     *
     * @return Collection
     */
    public function items() {
        return $this->items;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function to_array() {
        return [
            'current_page' => $this->current_page(),
            'data'         => $this->items->to_array(),
            'last_page'    => $this->last_page(),
            'per_page'     => $this->per_page(),
            'total'        => $this->total(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->to_array();
    }

    /**
     * Count the number of items.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count() {
        return $this->items->count();
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator() {
        return $this->items->getIterator();
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed  $key
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists( $key ) {
        return $this->items->offsetExists( $key );
    }

    /**
     * Get the item at the given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet( $key ) {
        return $this->items->offsetGet( $key );
    }

    /**
     * Set the item at the given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet( $key, $value ) {
        $this->items->offsetSet( $key, $value );
    }

    /**
     * Unset the item at the given key.
     *
     * @param  mixed  $key
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset( $key ) {
        $this->items->offsetUnset( $key );
    }
}

<?php
/**
 * Eloquent Collection class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( "ABSPATH" ) || exit;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use WpMVC\Database\Eloquent\Model;

/**
 * Class Collection
 *
 * Provides a wrapper for an array of models or other items.
 *
 * @package WpMVC\Database\Eloquent
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     */
    public function __construct( $items = [] ) {
        $this->items = $this->get_arrayable_items( $items );
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array
     */
    protected function get_arrayable_items( $items ) {
        if ( is_array( $items ) ) {
            return $items;
        } elseif ( $items instanceof self ) {
            return $items->all();
        }

        return (array) $items;
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all() {
        return $this->items;
    }

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first( ?callable $callback = null, $default = null ) {
        if ( is_null( $callback ) ) {
            if ( empty( $this->items ) ) {
                return $default;
            }

            foreach ( $this->items as $item ) {
                return $item;
            }
        }

        foreach ( $this->items as $key => $value ) {
            if ( call_user_func( $callback, $value, $key ) ) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the last item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last( ?callable $callback = null, $default = null ) {
        if ( is_null( $callback ) ) {
            return empty( $this->items ) ? $default : end( $this->items );
        }

        return ( new static( array_reverse( $this->items, true ) ) )->first( $callback, $default );
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map( callable $callback ) {
        $keys  = array_keys( $this->items );
        $items = array_map( $callback, $this->items, $keys );

        return new static( array_combine( $keys, $items ) );
    }

    /**
     * Execute a callback over each item.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each( callable $callback ) {
        foreach ( $this->items as $key => $item ) {
            if ( $callback( $item, $key ) === false ) {
                break;
            }
        }

        return $this;
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter( ?callable $callback = null ) {
        if ( $callback ) {
            return new static( array_filter( $this->items, $callback, ARRAY_FILTER_USE_BOTH ) );
        }

        return new static( array_filter( $this->items ) );
    }

    /**
     * Pluck an array of values from a given key.
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return static
     */
    public function pluck( $value, $key = null ) {
        $results = [];

        foreach ( $this->items as $item ) {
            $item_value = is_object( $item ) ? ( $item->{$value} ?? null ) : ( $item[$value] ?? null );

            if ( is_null( $key ) ) {
                $results[] = $item_value;
            } else {
                $item_key           = is_object( $item ) ? ( $item->{$key} ?? null ) : ( $item[$key] ?? null );
                $results[$item_key] = $item_value;
            }
        }

        return new static( $results );
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  string  $key
     * @param  mixed   $operator
     * @param  mixed   $value
     * @return static
     */
    public function where( $key, $operator = null, $value = null ) {
        if ( func_num_args() === 2 ) {
            $value    = $operator;
            $operator = '=';
        }

        return $this->filter(
            function ( $item ) use ( $key, $operator, $value ) {
                $retrieved = is_object( $item ) ? ( $item->{$key} ?? null ) : ( $item[$key] ?? null );

                switch ( $operator ) {
                    case '==':
                    case '=':
                        return $retrieved == $value;
                    case '===':
                        return $retrieved === $value;
                    case '!=':
                    case '<>':
                        return $retrieved != $value;
                    case '!==':
                        return $retrieved !== $value;
                    case '<':
                        return $retrieved < $value;
                    case '>':
                        return $retrieved > $value;
                    case '<=':
                        return $retrieved <= $value;
                    case '>=':
                        return $retrieved >= $value;
                }

                return false;
            } 
        );
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null  $key
     * @return static
     */
    public function unique( $key = null ) {
        if ( is_null( $key ) ) {
            return new static( array_unique( $this->items, SORT_REGULAR ) );
        }

        $exists = [];

        return $this->filter(
            function ( $item ) use ( $key, &$exists ) {
                $id = is_callable( $key ) ? $key( $item ) : ( is_object( $item ) ? $item->{$key} : $item[$key] );

                if ( in_array( $id, $exists, true ) ) {
                    return false;
                }

                $exists[] = $id;

                return true;
            }
        );
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  array|callable|string  $group_by
     * @return static
     */
    public function group_by( $group_by ) {
        $results = [];

        foreach ( $this->items as $key => $value ) {
            $group_keys = is_callable( $group_by ) ? $group_by( $value, $key ) : ( is_object( $value ) ? $value->{$group_by} : $value[$group_by] );

            if ( ! is_array( $group_keys ) ) {
                $group_keys = [$group_keys];
            }

            foreach ( $group_keys as $group_key ) {
                $results[$group_key][] = $value;
            }
        }

        return new static(
            array_map(
                function( $items ) {
                    return new static( $items );
                }, $results 
            )
        );
    }

    /**
     * Sort the collection using the given callback or key.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sort_by( $callback, $options = SORT_REGULAR, $descending = false ) {
        $results = [];

        $callback = is_callable( $callback ) ? $callback : function ( $item ) use ( $callback ) {
            return is_object( $item ) ? $item->{$callback} : $item[$callback];
        };

        foreach ( $this->items as $key => $value ) {
            $results[$key] = call_user_func( $callback, $value, $key );
        }

        $descending ? arsort( $results, $options ) : asort( $results, $options );

        foreach ( array_keys( $results ) as $key ) {
            $results[$key] = $this->items[$key];
        }

        return new static( $results );
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop() {
        return array_pop( $this->items );
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed  $item
     * @return $this
     */
    public function push( $item ) {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable  $callback
     * @param  mixed  $initial
     * @return mixed
     */
    public function reduce( callable $callback, $initial = null ) {
        return array_reduce( $this->items, $callback, $initial );
    }

    /**
     * Chunk the collection into chunks of a given size.
     *
     * @param  int  $size
     * @return static
     */
    public function chunk( int $size ) {
        if ( $size <= 0 ) {
            return new static( [] );
        }

        $chunks = [];
        foreach ( array_chunk( $this->items, $size ) as $chunk ) {
            $chunks[] = new static( $chunk );
        }

        return new static( $chunks );
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param  mixed  $item
     * @return bool
     */
    public function contains( $item ): bool {
        if ( is_callable( $item ) ) {
            foreach ( $this->items as $key => $value ) {
                if ( $item( $value, $key ) ) {
                    return true;
                }
            }
            return false;
        }

        return in_array( $item, $this->items, true );
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists( $key ) {
        return array_key_exists( $key, $this->items );
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet( $key ) {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet( $key, $value ) {
        if ( is_null( $key ) ) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset( $key ) {
        unset( $this->items[$key] );
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count() {
        return count( $this->items );
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator() {
        return new ArrayIterator( $this->items );
    }

    /**
     * Cache to prevent circular recursion during serialization.
     *
     * @var array
     */
    protected static array $serializing = [];

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function to_array() {
        $hash = spl_object_hash( $this );

        if ( isset( static::$serializing[$hash] ) ) {
            return [];
        }

        static::$serializing[$hash] = true;

        $results = array_map(
            function ( $value ) {
                return $value instanceof Model || $value instanceof self ? $value->to_array() : $value;
            }, $this->items 
        );

        unset( static::$serializing[$hash] );

        return $results;
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function is_empty() {
        return empty( $this->items );
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function is_not_empty() {
        return ! $this->is_empty();
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
}

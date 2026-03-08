<?php
/**
 * Model attribute handling trait.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Concerns;

defined( "ABSPATH" ) || exit;

use DateTime;
use DateTimeInterface;
use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Collection;
use WpMVC\Database\Eloquent\Relations\Relation;

/**
 * Trait HasAttributes
 *
 * Provides methods for managing model attributes, casting, and accessors/mutators.
 *
 * @package WpMVC\Database\Eloquent\Concerns
 */
trait HasAttributes {
    /**
     * The model's attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected array $appends = [];
    
    /**
     * The attributes that should be hidden from serialization.
     *
     * @var array
     */
    protected array $hidden = [];

    /**
     * The attributes that should be visible during serialization.
     *
     * @var array
     */
    protected array $visible = [];

    /**
     * The model's original attributes.
     *
     * @var array
     */
    protected array $original = [];

    /**
     * Cache to store instantiated/decoded casted values.
     *
     * @var array
     */
    protected array $class_cast_cache = [];

    /**
     * Cache to prevent circular recursion during serialization.
     *
     * @var array
     */
    protected static array $serializing = [];

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function to_array() {
        $hash = spl_object_hash( $this );

        if ( isset( static::$serializing[$hash] ) ) {
            return [];
        }

        static::$serializing[$hash] = true;

        try {
            $result = [];

            // 1. Process base attributes (with casting, but lacking accessors)
            foreach ( $this->attributes as $key => $value ) {
                if ( ! $this->is_visible_attribute( $key ) ) {
                    continue;
                }
                $result[$key] = $this->has_cast( $key ) ? $this->cast_attribute( $key, $value ) : $value;
            }

            // 2. Add/Override with appended attributes (which triggers accessors)
            foreach ( $this->appends as $append ) {
                if ( ! $this->is_visible_attribute( $append ) ) {
                    continue;
                }
                $result[$append] = $this->get_attribute( $append );
            }

            // 3. Process loaded relationships
            if ( method_exists( $this, 'get_relations' ) ) {
                foreach ( $this->get_relations() as $key => $value ) {
                    $result[$key] = $value;
                }
            }

            // 4. Serialize values for array representation
            foreach ( $result as $key => $value ) {
                if ( $value instanceof Model || $value instanceof Collection ) {
                    $result[$key] = $value->to_array();
                } elseif ( is_array( $value ) ) {
                    $result[$key] = array_map(
                        function( $item ) {
                            return $item instanceof Model || $item instanceof Collection ? $item->to_array() : $item;
                        }, $value 
                    );
                } elseif ( $value instanceof DateTimeInterface ) {
                    $result[$key] = $this->serialize_date( $key, $value );
                }
            }

            return $result;
        } finally {
            unset( static::$serializing[$hash] );
        }
    }

    /**
     * Serialize a date attribute.
     *
     * @param  string           $key
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serialize_date( string $key, DateTimeInterface $date ) {
        if ( ! $this->has_cast( $key ) ) {
            return $date->format( DateTime::ATOM );
        }

        $cast_type = $this->casts[$key];
        
        if ( strpos( $cast_type, 'date:' ) === 0 || strpos( $cast_type, 'datetime:' ) === 0 ) {
            list( , $format ) = explode( ':', $cast_type, 2 );
            return $date->format( $format );
        }

        if ( in_array( strtolower( trim( $cast_type ) ), ['date', 'datetime'] ) ) {
            return $date->format( 'Y-m-d H:i:s' );
        }

        return $date->format( DateTime::ATOM );
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get_attribute( $key ) {
        if ( ! $key ) {
            return;
        }

        // 1. If the attribute exists in the attributes array or has an accessor, get its value.
        if ( array_key_exists( $key, $this->attributes ) || $this->has_accessor( $key ) || $this->has_cast( $key ) ) {
            return $this->get_attribute_value( $key );
        }

        // 2. If the attribute exists as a loaded relationship, return it.
        if ( method_exists( $this, 'relation_loaded' ) && $this->relation_loaded( $key ) ) {
            return $this->get_relation( $key );
        }

        // 3. Fallback to lazy-loading relationships if the method exists.
        if ( method_exists( $this, $key ) ) {
            $relation = $this->$key();

            if ( $relation instanceof Relation ) {
                $results = $relation->get_results();
                
                // Cache it so we don't repeatedly query the DB
                if ( method_exists( $this, 'set_relation' ) ) {
                    $this->set_relation( $key, $results );
                } else {
                    $this->attributes[$key] = $results;
                }
                
                return $results;
            }
        }

        return null;
    }

    /**
     * Get the value of an attribute after applying accessors and casts.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function get_attribute_value( string $key ) {
        $value = $this->attributes[$key] ?? null;

        // If the attribute has an accessor, call it.
        if ( $this->has_accessor( $key ) ) {
            return $this->{$this->get_accessor_method( $key )}( $value );
        }

        // If the attribute has a cast, apply it.
        if ( $this->has_cast( $key ) ) {
            return $this->cast_attribute( $key, $value );
        }

        return $value;
    }

    /**
     * Set an attribute's value, applying mutators if they exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function set_attribute( string $key, $value ) {
        if ( $this->has_mutator( $key ) ) {
            $this->{$this->get_mutator_method( $key )}( $value );
            return $this;
        }

        if ( is_null( $value ) ) {
            $this->attributes[$key] = null;
            return $this;
        }

        if ( $this->has_cast( $key ) ) {
            $cast_type = $this->casts[$key];
            if ( strpos( $cast_type, ':' ) !== false ) {
                list( $type, ) = explode( ':', $cast_type, 2 );
                $type          = strtolower( trim( $type ) );
            } else {
                $type = strtolower( trim( $cast_type ) );
            }

            if ( in_array( $type, ['array', 'json', 'object'] ) ) {
                if ( ! is_string( $value ) ) {
                    $value = json_encode( $value );
                }
            } elseif ( in_array( $type, ['date', 'datetime'] ) && $value instanceof DateTimeInterface ) {
                $value = $value->format( 'Y-m-d H:i:s' );
            }
        }
        if ( method_exists( $this, 'relation_loaded' ) && ( $this->relation_loaded( $key ) || ( method_exists( $this, $key ) && ! Model::is_reserved_method( $key ) ) ) ) {
            return $this->set_relation( $key, $value );
        }

        $this->attributes[$key] = $value;

        // Clear the cache for this attribute if it exists
        if ( isset( $this->class_cast_cache[$key] ) ) {
            unset( $this->class_cast_cache[$key] );
        }

        return $this;
    }

    /**
     * Cast an attribute to a native type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function cast_attribute( string $key, $value ) {
        if ( is_null( $value ) ) {
            return $value;
        }

        $cast_type = $this->casts[$key];
        
        if ( strpos( $cast_type, ':' ) !== false ) {
            list( $type, $param ) = explode( ':', $cast_type, 2 );
            $type                 = strtolower( trim( $type ) );
        } else {
            $type  = strtolower( trim( $cast_type ) );
            $param = null;
        }

        switch ( $type ) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'decimal':
                return number_format( (float) $value, (int) $param, '.', '' );
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'date':
            case 'datetime':
                if ( isset( $this->class_cast_cache[$key] ) ) {
                    return $this->class_cast_cache[$key];
                }
                return $this->class_cast_cache[$key] = $value instanceof DateTimeInterface ? $value : new DateTime( $value );
            case 'object':
                if ( isset( $this->class_cast_cache[$key] ) ) {
                    return $this->class_cast_cache[$key];
                }
                return $this->class_cast_cache[$key] = is_string( $value ) ? json_decode( $value ) : $value;
            case 'array':
            case 'json':
                if ( isset( $this->class_cast_cache[$key] ) ) {
                    return $this->class_cast_cache[$key];
                }
                return $this->class_cast_cache[$key] = is_string( $value ) ? json_decode( $value, true ) : $value;
            default:
                return $value;
        }
    }

    /**
     * Determine if a cast exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    protected function has_cast( string $key ): bool {
        return array_key_exists( $key, $this->casts );
    }

    /**
     * Determine if an accessor exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    protected function has_accessor( string $key ): bool {
        return method_exists( $this, $this->get_accessor_method( $key ) );
    }

    /**
     * Get the method name for an attribute's accessor.
     *
     * @param  string  $key
     * @return string
     */
    protected function get_accessor_method( string $key ): string {
        return 'get_' . $key . '_attribute';
    }

    /**
     * Determine if a mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    protected function has_mutator( string $key ): bool {
        return method_exists( $this, $this->get_mutator_method( $key ) );
    }

    /**
     * Get the method name for an attribute's mutator.
     *
     * @param  string  $key
     * @return string
     */
    protected function get_mutator_method( string $key ): string {
        return 'set_' . $key . '_attribute';
    }

    /**
     * Get all attributes from the model.
     *
     * @return array
     */
    public function get_attributes() {
        return $this->attributes;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function sync_original() {
        $this->original         = $this->attributes;
        $this->class_cast_cache = [];

        return $this;
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function get_dirty() {
        $dirty = [];

        foreach ( $this->attributes as $key => $value ) {
            if ( ! array_key_exists( $key, $this->original ) ) {
                $dirty[$key] = $value;
                continue;
            }

            $original = $this->original[$key];

            if ( $value === $original ) {
                continue;
            }

            if ( $value instanceof DateTimeInterface && $original instanceof DateTimeInterface ) {
                if ( $value->getTimestamp() === $original->getTimestamp() ) {
                    continue;
                }
            }

            if ( $this->has_cast( $key ) ) {
                $cast_type = strtolower( trim( $this->casts[$key] ) );
                if ( in_array( $cast_type, ['json', 'array', 'object'] ) ) {
                    $decoded_value    = is_string( $value ) ? json_decode( $value, true ) : $value;
                    $decoded_original = is_string( $original ) ? json_decode( $original, true ) : $original;
                    if ( $decoded_value === $decoded_original ) {
                        continue;
                    }
                }
            }

            $dirty[$key] = $value;
        }

        return $dirty;
    }

    /**
     * Determine if an attribute is visible for serialization.
     *
     * @param string $key
     * @return bool
     */
    protected function is_visible_attribute( string $key ): bool {
        if ( count( $this->visible ) > 0 ) {
            return in_array( $key, $this->visible );
        }

        return ! in_array( $key, $this->hidden );
    }

    /**
     * Set the hidden attributes for the model.
     *
     * @param array $hidden
     * @return $this
     */
    public function make_hidden( array $hidden ) {
        $this->hidden = array_merge( $this->hidden, $hidden );
        return $this;
    }

    /**
     * Set the visible attributes for the model.
     *
     * @param array $visible
     * @return $this
     */
    public function make_visible( array $visible ) {
        $this->visible = array_merge( $this->visible, $visible );
        return $this;
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * @param array|string|null $attributes
     * @return bool
     */
    public function is_dirty( $attributes = null ) {
        $all_args = func_get_args();
        $dirty    = $this->get_dirty();

        if ( is_null( $attributes ) ) {
            return count( $dirty ) > 0;
        }

        $args = is_array( $attributes ) ? $attributes : $all_args;

        foreach ( $args as $attribute ) {
            if ( array_key_exists( $attribute, $dirty ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function get_key_name() {
        return $this->primary_key;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function get_key() {
        return $this->get_attribute( $this->get_key_name() );
    }

    /**
     * Get the fully qualified primary key for the model.
     *
     * @return string
     */
    public function get_qualified_key_name() {
        return static::get_table_name() . '.' . $this->get_key_name();
    }
}

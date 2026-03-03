<?php
/**
 * Eloquent Model class.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( "ABSPATH" ) || exit;

use Closure;
use JsonSerializable;
use WpMVC\Database\Query\Builder;
use WpMVC\Database\Resolver;
use WpMVC\Database\Eloquent\Concerns\HasAttributes;
use WpMVC\Database\Eloquent\Concerns\HasEvents;
use WpMVC\Database\Eloquent\Concerns\HasRelationships;
use WpMVC\Database\Eloquent\Concerns\HasTimestamps;
use WpMVC\Database\Eloquent\Concerns\GuardsAttributes;

/**
 * Class Model
 *
 * The base class for all Eloquent models in WpMVC.
 *
 * @package WpMVC\Database\Eloquent
 *
 * @method static Builder with(string|array $relations, string|(Closure(static): mixed)|array|null $callback = null)
 * @method static Builder with_count(mixed $relations, callable|null $callback = null)
 * @method static Builder where(string|callable|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static Builder where_in(string $column, array $values, string $boolean = 'and', bool $not = false)
 * @method static Builder where_not_in(string $column, array $values, string $boolean = 'and')
 * @method static Builder where_null(string $column, string $boolean = 'and', bool $not = false)
 * @method static Builder where_not_null(string $column, string $boolean = 'and')
 * @method static Builder latest(string $column = 'created_at')
 * @method static Builder oldest(string $column = 'created_at')
 * @method static Builder order_by(string $column, string $direction = 'asc')
 * @method static Builder limit(int $value)
 * @method static Builder offset(int $value)
 * @method static Collection all()
 * @method static static|null find(mixed $id)
 * @method static static find_or_fail(mixed $id)
 * @method static static|null first()
 * @method static static first_or_fail()
 * @method Builder with(string|array $relations, string|(Closure(static): mixed)|array|null $callback = null)
 * @method Builder with_count(mixed $relations, callable|null $callback = null)
 * @method Builder where(string|callable|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method Builder where_in(string $column, array $values, string $boolean = 'and', bool $not = false)
 * 
 * @mixin Builder
 */
#[\AllowDynamicProperties]
abstract class Model implements JsonSerializable {
    use HasAttributes, 
        HasEvents, 
        HasRelationships, 
        HasTimestamps, 
        GuardsAttributes;

    /**
     * Indicates if the model exists in the database.
     *
     * @var bool
     */
    public bool $exists = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public bool $incrementing = true;

    /**
     * Map of morph types to class names.
     *
     * @var array
     */
    public static $morph_map = [];

    /**
     * The internal morph type for the model (used for polymorphic matching).
     *
     * @var string|null
     */
    public ?string $_morph_type = null;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct( array $attributes = [] ) {
        $this->fill( $attributes );
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save() {
        if ( $this->fire_model_event( 'saving' ) === false ) {
            return false;
        }

        if ( $this->timestamps ) {
            $this->update_timestamps();
        }

        $query = $this->new_query();

        if ( $this->exists ) {
            if ( $this->fire_model_event( 'updating' ) === false ) {
                return false;
            }

            $dirty = $this->get_dirty();

            if ( empty( $dirty ) ) {
                return true;
            }

            $query->where( $this->get_key_name(), $this->get_attribute( $this->get_key_name() ) )
                  ->update( $dirty );

            $this->fire_model_event( 'updated', false );
        } else {
            if ( $this->fire_model_event( 'creating' ) === false ) {
                return false;
            }

            if ( $this->incrementing ) {
                $id = $query->insert_get_id( $this->attributes );
                if ( ! $id ) {
                    return false;
                }
                $this->set_attribute( $this->get_key_name(), $id );
            } else {
                if ( ! $query->insert( $this->attributes ) ) {
                    return false;
                }
            }

            $this->exists = true;

            $this->fire_model_event( 'created', false );
        }

        $this->sync_original();

        $this->fire_model_event( 'saved', false );

        return true;
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @return bool
     */
    public function update( array $attributes = [] ) {
        if ( ! $this->exists ) {
            return false;
        }

        return $this->fill( $attributes )->save();
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete() {
        if ( ! $this->exists ) {
            return false;
        }

        if ( $this->fire_model_event( 'deleting' ) === false ) {
            return false;
        }

        $this->new_query()
             ->where( $this->get_key_name(), $this->get_attribute( $this->get_key_name() ) )
             ->delete();

        $this->exists = false;
        unset( $this->attributes[$this->get_key_name()] );

        $this->fire_model_event( 'deleted', false );

        return true;
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @param  string|null  $as
     * @return Builder
     */
    public static function query( $as = null ) {
        return ( new static )->new_query( $as );
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function create( array $attributes = [] ) {
        $model = new static( $attributes );

        $model->save();

        return $model;
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @param  string|null  $as
     * @return Builder
     */
    public function new_query( $as = null ) {
        $builder = new Builder( $this );
        
        $builder->from( static::get_table_name(), $as );

        return $builder;
    }

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    abstract static function get_table_name(): string;

    /**
     * Get the fully qualified table name (with prefix).
     *
     * @return string
     */
    public function get_table() {
        return $this->resolver()->table( static::get_table_name() );
    }

    /**
     * Get the resolver instance.
     *
     * @return Resolver
     */
    abstract public function resolver(): Resolver;

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function get_foreign_key() {
        return static::get_foreign_key_from_class( static::class );
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return Collection
     */
    public function new_collection( array $models = [] ) {
        return new Collection( $models );
    }

    /**
     * Determine if a method name is reserved by the model.
     *
     * @param  string  $method
     * @return bool
     */
    public static function is_reserved_method( string $method ) {
        static $reserved = [
            // Core Model methods
            'save', 'update', 'delete', 'fill', 'query', 'new_query', 'all', 'create', 'find', 'find_or_fail',
            'get_table_name', 'resolver', 'new_collection', 'get_foreign_key', 'json_serialize',
            
            // HasAttributes methods
            'to_array', 'get_attribute', 'set_attribute', 'sync_original', 'get_dirty', 'is_dirty',
            'get_key_name', 'get_qualified_key_name', 'get_attributes', 'serialize_date',
            
            // HasRelationships methods
            'set_relation', 'get_relation', 'relation_loaded', 'unset_relation', 'get_relations',
            'morph_map', 'get_morph_class', 'has_many', 'has_one', 'belongs_to', 'belongs_to_many',
            'has_one_of_many', 'has_many_through', 'has_one_through', 'morph_to', 'morph_one',
            'morph_many', 'morph_to_many', 'morphed_by_many', 'get_foreign_key_from_class',
            
            'fire_model_event', 'observe', 'register_observer', 'update_timestamps', 
            'is_fillable', 'totally_guarded', 'get_fillable', 'fillable', 'get_guarded', 'guarded',
            'make_hidden', 'make_visible'
        ];

        return in_array( strtolower( $method ), $reserved );
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get( $key ) {
        return $this->get_attribute( $key );
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set( $key, $value ) {
        if ( $this->relation_loaded( $key ) || ( method_exists( $this, $key ) && ! static::is_reserved_method( $key ) ) ) {
            $this->set_relation( $key, $value );
            return;
        }

        $this->set_attribute( $key, $value );
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset( $key ) {
        return isset( $this->attributes[$key] ) || $this->relation_loaded( $key );
    }

    /**
     * Dynamically unset attributes on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset( $key ) {
        unset( $this->attributes[$key] );

        if ( method_exists( $this, 'unset_relation' ) ) {
            $this->unset_relation( $key );
        }
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call( $method, $parameters ) {
        return $this->new_query()->$method( ...$parameters );
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic( $method, $parameters ) {
        return ( new static )->$method( ...$parameters );
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
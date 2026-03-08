<?php
/**
 * Base Eloquent Factory class for WpMVC.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent;

defined( 'ABSPATH' ) || exit;

use Closure;
use InvalidArgumentException;
use WpMVC\Database\Eloquent\Collection;

/**
 * Class Factory
 *
 * Represents an Eloquent model factory.
 */
abstract class Factory {
    /**
     * The model class that the factory is for.
     *
     * @var string|null
     */
    protected $model;

    /**
     * The number of models to generate.
     *
     * @var int|null
     */
    protected $count;

    /**
     * The state transformations.
     *
     * @var array
     */
    protected array $states = [];

    /**
     * The models that should be recycled.
     *
     * @var array
     */
    protected array $recycled = [];

    /**
     * The after making callbacks.
     *
     * @var array
     */
    protected array $after_making = [];

    /**
     * The after creating callbacks.
     *
     * @var array
     */
    protected array $after_creating = [];

    /**
     * The custom factory name resolver.
     *
     * @var callable|null
     */
    protected static $factory_name_resolver;

    /**
     * Indicates if model events should be disabled.
     *
     * @var bool
     */
    protected bool $without_events = false;

    /**
     * Get the model factory definition.
     *
     * @return array
     */
    abstract public function definition(): array;

    /**
     * Get a new factory instance for the given model.
     *
     * @param  string  $model_class
     * @return static
     */
    public static function new_factory_for_model( string $model_class ) {
        if ( static::$factory_name_resolver ) {
            $factory_name = ( static::$factory_name_resolver )( $model_class );
            if ( class_exists( $factory_name ) ) {
                return ( new $factory_name() )->configure();
            }
        }

        // Guess factory name: App\Models\User -> Database\Factories\UserFactory
        // Handle scoping: MyPluginNamespace\App\Models\User -> MyPluginNamespace\Database\Factories\UserFactory
        
        $factory_name = str_replace( ['\\Models\\', 'Models\\'], ['\\Factories\\', 'Factories\\'], $model_class ) . 'Factory';
        $factory_name = str_replace( 'App\\', 'Database\\', $factory_name );

        if ( class_exists( $factory_name ) ) {
            return ( new $factory_name() )->configure();
        }

        throw new InvalidArgumentException( "Factory not found for model: {$model_class} (Guessed: {$factory_name})" );
    }

    /**
     * Specify the callback to use to guess factory names.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function guess_factory_names_using( callable $callback ) {
        static::$factory_name_resolver = $callback;
    }

    /**
     * Create a new factory instance.
     *
     * @param  int|null  $count
     * @return static
     */
    public static function new( ?int $count = null ) {
        $factory = new static();

        if ( $count ) {
            $factory->count = $count;
        }

        return $factory->configure();
    }

    /**
     * Set the number of models that should be generated.
     *
     * @param  int|null  $count
     * @return $this
     */
    public function count( ?int $count = null ) {
        $this->count = $count;

        return $this;
    }

    /**
     * Add a new state transformation to the model definition.
     *
     * @param  array|callable  $state
     * @return $this
     */
    public function state( $state ) {
        $this->states[] = $state;

        return $this;
    }

    /**
     * Recycle a specific model instance.
     *
     * @param  Model|Collection|array  $models
     * @return $this
     */
    public function recycle( $models ) {
        if ( ! is_array( $models ) && ! $models instanceof Collection ) {
            $models = [$models];
        }

        foreach ( $models as $model ) {
            $this->recycled[get_class( $model )][] = $model;
        }

        return $this;
    }

    /**
     * Disable model events for the model.
     *
     * @return $this
     */
    public function without_events() {
        $this->without_events = true;

        return $this;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @return Model|Collection
     */
    public function create( array $attributes = [] ) {
        $results = $this->make( $attributes );

        if ( $results instanceof Model ) {
            $this->store( [$results] );
        } else {
            $this->store( $results->all() );
        }

        return $results;
    }

    /**
     * Create a collection of models and return them.
     *
     * @param  array  $attributes
     * @return Model|Collection
     */
    public function make( array $attributes = [] ) {
        if ( $this->count === null ) {
            $model = $this->make_instance( $attributes );
            $this->call_after_making( [$model] );
            return $model;
        }

        if ( $this->count < 1 ) {
            return new Collection();
        }

        $instances = [];
        for ( $i = 0; $i < $this->count; $i++ ) {
            $instances[] = $this->make_instance( $attributes );
        }

        $collection = new Collection( $instances );
        $this->call_after_making( $collection->all() );

        return $collection;
    }

    /**
     * Get the raw attributes for the models.
     *
     * @param  array  $attributes
     * @return array
     */
    public function raw( array $attributes = [] ) {
        if ( $this->count === null ) {
            return $this->get_raw_attributes( $attributes );
        }

        if ( $this->count < 1 ) {
            return [];
        }

        $results = [];
        for ( $i = 0; $i < $this->count; $i++ ) {
            $results[] = $this->get_raw_attributes( $attributes );
        }

        return $results;
    }

    /**
     * Create a single instance of the model.
     *
     * @param  array  $attributes
     * @return Model
     */
    protected function make_instance( array $attributes ) {
        $model_class    = $this->model_name();
        $raw_attributes = $this->get_raw_attributes( $attributes );
        
        $model_class::unguard();
        $model = new $model_class( $raw_attributes );
        $model_class::reguard();

        return $model;
    }

    /**
     * Get the raw attributes for a single model instance.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function get_raw_attributes( array $attributes ) {
        $definition = $this->definition();

        foreach ( $this->states as $state ) {
            if ( is_callable( $state ) ) {
                $definition = array_merge( $definition, $state( $definition ) );
            } elseif ( $state instanceof Sequence ) {
                $definition = array_merge( $definition, $state->next( $this->count ?? 1 ) );
            } else {
                $definition = array_merge( $definition, $state );
            }
        }

        $definition = array_merge( $definition, $attributes );

        // Resolve attributes that are factories or closures
        return $this->expand_attributes( $definition );
    }

    /**
     * Expand any attributes that are factories or closures.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function expand_attributes( array $attributes ) {
         /**
         * @var Model $model
         */
        foreach ( $attributes as $key => &$value ) {
            if ( $value instanceof Factory ) {
                $model_class = $value->model_name();
                if ( isset( $this->recycled[$model_class] ) ) {
                    $model = $this->recycled[$model_class][array_rand( $this->recycled[$model_class] )];
                    $value = $model->get_key();
                } else {
                    // Propagate recycled models to nested factories
                    foreach ( $this->recycled as $recycled_models ) {
                        $value->recycle( $recycled_models );
                    }

                    $model = $value->create();
                    $value = $model->get_key();
                    if ( is_null( $value ) ) {
                        error_log( "Factory Debug: Created model " . get_class( $model ) . " but get_key() returned NULL. Attributes: " . json_encode( $model->to_array() ) );
                    }
                }
            } elseif ( $value instanceof Closure ) {
                $value = $value( $attributes );
            } elseif ( $value instanceof Sequence ) {
                $value = $value->next( $this->count ?? 1 );
            }
        }

        return $attributes;
    }

    /**
     * Store the given models.
     *
     * @param  array  $models
     * @return void
     */
    protected function store( array $models ) {
        foreach ( $models as $model ) {
            if ( $this->without_events ) {
                // This would require a proper way to disable events in the Model itself
                // but for now we'll just fire the saving events manually or skip if possible.
                // In WpMVC Model, we can probably use a flag if we modify it, 
                // but let's assume we skip fire_model_event if we had a flag.
            }
            $model->save();
        }

        $this->call_after_creating( $models );
    }

    /**
     * Get the model class name.
     *
     * @return string
     */
    public function model_name() {
        if ( $this->model ) {
            return $this->model;
        }

        $factory_name = get_class( $this );
        
        // Handle scoping: MyPluginNamespace\Database\Factories\UserFactory -> MyPluginNamespace\App\Models\User
        $model_class = str_replace( ['\\Factories\\', 'Factories\\'], ['\\Models\\', 'Models\\'], $factory_name );
        $model_class = str_replace( 'Database\\', 'App\\', $model_class );
        $model_class = str_replace( 'Factory', '', $model_class );

        return $model_class;
    }

    /**
     * Configure the factory.
     *
     * @return $this
     */
    public function configure() {
        return $this;
    }

    /**
     * Get the Faker instance.
     *
     * @return FakeData
     */
    protected function faker() {
        return FakeData::instance();
    }

    /**
     * Proxy calls to state for magic states.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call( $method, $parameters ) {
        if ( method_exists( $this, $method ) ) {
            return $this->{$method}( ...$parameters );
        }

        // Handle magic has{Relation} and for{Relation}
        if ( strpos( $method, 'has' ) === 0 ) {
            $relationship = lcfirst( substr( $method, 3 ) );
            $factory      = $parameters[0] ?? null;
            $count        = $parameters[1] ?? null;

            if ( ! $factory instanceof Factory ) {
                $model_class = $this->model_name();
                $model       = new $model_class();
                
                if ( method_exists( $model, $relationship ) ) {
                    $relation_obj = $model->$relationship();
                    // Interrogate relation for related model
                    try {
                        $ref = new \ReflectionProperty( $relation_obj, 'related' );
                        if ( \PHP_VERSION_ID < 80100 ) {
                            $ref->setAccessible( true );
                        }
                        $related_model = $ref->getValue( $relation_obj );
                        $factory       = static::new_factory_for_model( get_class( $related_model ) );
                        $count         = $parameters[0] ?? null;
                    } catch ( \Exception $e ) {
                        // Fallback to old guessing
                    }
                }

                if ( ! $factory instanceof Factory ) {
                    // Fallback guessing logic
                    $guessed_model = 'App\\Models\\' . ucfirst( rtrim( $relationship, 's' ) );
                    if ( strpos( get_class( $this ), 'MyPluginNamespace' ) === 0 ) {
                        $guessed_model = 'MyPluginNamespace\\' . $guessed_model;
                    }
                    $factory = static::new_factory_for_model( $guessed_model );
                    $count   = $parameters[0] ?? null;
                }
            }

            return $this->has( $factory, $count, $relationship );
        }

        if ( strpos( $method, 'for' ) === 0 ) {
            $relationship = lcfirst( substr( $method, 3 ) );
            return $this->for( $parameters[0], $relationship );
        }

        // Try to call magic state method
        // e.g., published() -> state(['status' => 'published'])
        return $this->state( [$method => true] );
    }

    /**
     * Register an after making callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after_making( callable $callback ) {
        $this->after_making[] = $callback;

        return $this;
    }

    /**
     * Register an after creating callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after_creating( callable $callback ) {
        $this->after_creating[] = $callback;

        return $this;
    }

    /**
     * Call the after making callbacks.
     *
     * @param  array  $models
     * @return void
     */
    protected function call_after_making( array $models ) {
        foreach ( $models as $model ) {
            foreach ( $this->after_making as $callback ) {
                $callback( $model );
            }
        }
    }

    /**
     * Call the after creating callbacks.
     *
     * @param  array  $models
     * @return void
     */
    protected function call_after_creating( array $models ) {
        foreach ( $models as $model ) {
            foreach ( $this->after_creating as $callback ) {
                $callback( $model );
            }
        }
    }

    /**
     * Define a related model factory.
     *
     * @param  Factory  $factory
     * @param  int|string|null  $count
     * @param  string|null  $relationship
     * @return $this
     */
    public function has( Factory $factory, $count = null, ?string $relationship = null ) {
        return $this->after_creating(
            function( Model $model ) use ( $factory, $count, $relationship ) {
                $factory = clone $factory;

                if ( is_integer( $count ) ) {
                      $factory->count( $count );
                } elseif ( is_string( $count ) ) {
                    $relationship = $count;
                }

                $relationship = $relationship ?: strtolower( ( new \ReflectionClass( $factory->model_name() ) )->getShortName() ) . 's';
            
                $foreign_key = $model->get_foreign_key();

                // Interrogate the relationship if it exists on the model
                if ( method_exists( $model, $relationship ) ) {
                    $relation_obj = $model->$relationship();
                    // We use reflection because the property is protected in WpMVC base Relations
                    try {
                        $ref = new \ReflectionProperty( $relation_obj, 'foreign_key' );
                        if ( \PHP_VERSION_ID < 80100 ) {
                            $ref->setAccessible( true );
                        }
                        $foreign_key = $ref->getValue( $relation_obj );
                    } catch ( \Exception $e ) {
                        // Fallback to default
                    }
                }
            
                // Pass the model being created to the related factory for recycling
                $factory->recycle( $model )->create( [$foreign_key => $model->get_key()] );
            } 
        );
    }

    /**
     * Set the parent model for the factory.
     *
     * @param  Model|Factory  $parent
     * @param  string|null  $relationship
     * @return $this
     */
    public function for( $parent, ?string $relationship = null ) {
        return $this->state(
            function() use ( $parent, $relationship ) {
                $instance = $parent instanceof Factory ? $parent->create() : $parent;
            
                // Try to determine if it's polymorphic or simple
                $foreign_key = null;
                $class_parts = explode( '\\', get_class( $instance ) );
                $base_name   = strtolower( end( $class_parts ) );

                if ( $relationship ) {
                      // If the model has a method with this name, interrogate it
                      $model_class = $this->model_name();
                      $model       = new $model_class();
                    if ( method_exists( $model, $relationship ) ) {
                        $relation_obj = $model->$relationship();
                        // We check if it's a MorphTo relationship
                        $is_morph_to = false;
                        $parts       = explode( '\\', get_class( $relation_obj ) );
                        if ( end( $parts ) === 'MorphTo' ) {
                              $is_morph_to = true;
                        }

                        if ( $is_morph_to ) {
                            return [
                                $relationship . '_id'   => $instance->get_key(),
                                $relationship . '_type' => get_class( $instance ),
                            ];
                        }

                        // Simple belongsTo - try to find the foreign key
                        try {
                            $ref = new \ReflectionProperty( $relation_obj, 'foreign_key' );
                            if ( \PHP_VERSION_ID < 80100 ) {
                                $ref->setAccessible( true );
                            }
                            $foreign_key = $ref->getValue( $relation_obj );
                        } catch ( \Exception $e ) {
                            // Fallback
                        }
                    }
                
                    if ( ! $foreign_key ) {
                        $foreign_key = $relationship . '_id';
                    }
                } else {
                    // Guess based on convention
                    $foreign_key = $base_name . '_id';
                }

                return [$foreign_key => $instance->get_key()];
            } 
        );
    }
}

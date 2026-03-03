<?php
/**
 * Model relationship handling trait.
 *
 * @package WpMVC\Database
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Database\Eloquent\Concerns;

defined( "ABSPATH" ) || exit;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\BelongsTo;
use WpMVC\Database\Eloquent\Relations\BelongsToMany;
use WpMVC\Database\Eloquent\Relations\HasMany;
use WpMVC\Database\Eloquent\Relations\HasManyThrough;
use WpMVC\Database\Eloquent\Relations\HasOne;
use WpMVC\Database\Eloquent\Relations\HasOneOfMany;
use WpMVC\Database\Eloquent\Relations\HasOneThrough;
use WpMVC\Database\Eloquent\Relations\MorphMany;
use WpMVC\Database\Eloquent\Relations\MorphOne;
use WpMVC\Database\Eloquent\Relations\MorphTo;
use WpMVC\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait HasRelationships
 *
 * Provides methods for defining and managing model relationships.
 *
 * @package WpMVC\Database\Eloquent\Concerns
 */
trait HasRelationships {
    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected array $relations = [];

    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function set_relation( string $relation, $value ) {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Get a specific relationship from the model.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function get_relation( string $relation ) {
        return $this->relations[$relation] ?? null;
    }

    /**
     * Determine if the given relationship is loaded.
     *
     * @param  string  $key
     * @return bool
     */
    public function relation_loaded( string $key ) {
        return array_key_exists( $key, $this->relations );
    }

    /**
     * Unset a loaded relationship.
     *
     * @param  string  $relation
     * @return $this
     */
    public function unset_relation( string $relation ) {
        unset( $this->relations[$relation] );

        return $this;
    }

    /**
     * Get all of the loaded relationships for the model.
     *
     * @return array
     */
    public function get_relations() {
        return $this->relations;
    }

    /**
     * Get the actual class name for a given morph alias.
     *
     * @param  string  $alias
     * @return string
     */
    public static function get_actual_class_for_morph( $alias ) {
        $morph_map = static::morph_map();

        return isset( $morph_map[$alias] ) ? $morph_map[$alias] : $alias;
    }

    /**
     * Get the morph name for a given class.
     *
     * @param  string  $class
     * @return string
     */
    public static function get_morph_class_for( $class ) {
        $morph_map = static::morph_map();

        if ( ! empty( $morph_map ) && $alias = array_search( $class, $morph_map ) ) {
            return $alias;
        }

        return $class;
    }

    /**
     * Get or set the morph map for polymorphic relationships.
     *
     * @param  array|null  $map
     * @param  bool  $merge
     * @return array
     */
    public static function morph_map( ?array $map = null, $merge = true ) {
        if ( is_array( $map ) ) {
            static::$morph_map = $merge && ! empty( static::$morph_map )
                ? $map + static::$morph_map
                : $map;
        }

        return static::$morph_map;
    }

    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function get_morph_class() {
        return static::get_morph_class_for( static::class );
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param  string $related
     * @param  string $foreign_key
     * @param  string $local_key
     * @return HasMany
     */
    public function has_many( string $related, ?string $foreign_key = null, ?string $local_key = null ) {
        /** @var Model $instance */
        $instance    = new $related;
        $foreign_key = $foreign_key ?: $this->get_foreign_key();
        $local_key   = $local_key ?: $this->get_key_name();

        return new HasMany( $instance->new_query(), $this, $foreign_key, $local_key );
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param  string $related
     * @param  string  $foreign_key
     * @param  string  $local_key
     * @return HasOne
     */
    public function has_one( $related, ?string $foreign_key = null, ?string $local_key = null ) {
        /** @var Model $instance */
        $instance    = new $related;
        $foreign_key = $foreign_key ?: $this->get_foreign_key();
        $local_key   = $local_key ?: $this->get_key_name();

        return new HasOne( $instance->new_query(), $this, $foreign_key, $local_key );
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string $related
     * @param  string|null  $foreign_key (on the child model)
     * @param  string|null  $owner_key   (on the parent model)
     * @return BelongsTo
     */
    public function belongs_to( $related, ?string $foreign_key = null, ?string $owner_key = null ) {
        /** @var Model $instance */
        $instance    = new $related;
        $foreign_key = $foreign_key ?: $instance->get_foreign_key();
        $owner_key   = $owner_key ?: $instance->get_key_name();

        return new BelongsTo( $instance->new_query(), $this, $foreign_key, $owner_key );
    }

    /**
     * Define an inverse many-to-many relationship.
     *
     * @param  string $related
     * @param  string|Model $pivot
     * @param  string $foreign_pivot_key
     * @param  string $local_pivot_key
     * @param  string $foreign_key
     * @param  string $local_key
     * @return BelongsToMany
     */
    public function belongs_to_many( $related, $pivot, $foreign_pivot_key = null, $local_pivot_key = null, $foreign_key = null, $local_key = null ) {
        /** @var Model $instance */
        $instance          = new $related;
        $foreign_pivot_key = $foreign_pivot_key ?: $this->get_foreign_key();
        $local_pivot_key   = $local_pivot_key ?: $instance->get_foreign_key();
        $foreign_key       = $foreign_key ?: $instance->get_key_name();
        $local_key         = $local_key ?: $this->get_key_name();

        return new BelongsToMany( $instance->new_query(), $this, $pivot, $foreign_pivot_key, $local_pivot_key, $foreign_key, $local_key );
    }

    /**
     * Define a one-to-one of many relationship.
     *
     * @param  string $related
     * @param  string $foreign_key
     * @param  string $local_key
     * @param  string $sort_column
     * @param  string $sort_direction
     * @return HasOneOfMany
     */
    public function has_one_of_many( $related, ?string $foreign_key = null, ?string $local_key = null, string $sort_column = 'id', string $sort_direction = 'desc' ) {
        /** @var Model $instance */
        $instance    = new $related;
        $foreign_key = $foreign_key ?: $this->get_foreign_key();
        $local_key   = $local_key ?: $this->get_key_name();

        return new HasOneOfMany( $instance->new_query(), $this, $foreign_key, $local_key, $sort_column, $sort_direction );
    }

    /**
     * Define a has-many-through relationship.
     *
     * @param  string $related
     * @param  string $through
     * @param  string $first_key
     * @param  string $second_key
     * @param  string $local_key
     * @param  string $second_local_key
     * @return HasManyThrough
     */
    public function has_many_through( $related, $through, $first_key = null, $second_key = null, $local_key = null, $second_local_key = null ) {
        /** @var Model $instance */
        $instance = new $related;
        /** @var Model $through_instance */
        $through_instance = new $through;

        $first_key        = $first_key ?: $this->get_foreign_key();
        $second_key       = $second_key ?: $through_instance->get_foreign_key();
        $local_key        = $local_key ?: $this->get_key_name();
        $second_local_key = $second_local_key ?: $through_instance->get_key_name();

        return new HasManyThrough( $instance->new_query(), $this, $through_instance, $first_key, $second_key, $local_key, $second_local_key );
    }

    /**
     * Define a has-one-through relationship.
     *
     * @param  string $related
     * @param  string $through
     * @param  string $first_key
     * @param  string $second_key
     * @param  string $local_key
     * @param  string $second_local_key
     * @return HasOneThrough
     */
    public function has_one_through( $related, $through, $first_key = null, $second_key = null, $local_key = null, $second_local_key = null ) {
        /** @var Model $instance */
        $instance = new $related;
        /** @var Model $through_instance */
        $through_instance = new $through;

        $first_key        = $first_key ?: $this->get_foreign_key();
        $second_key       = $second_key ?: $through_instance->get_foreign_key();
        $local_key        = $local_key ?: $this->get_key_name();
        $second_local_key = $second_local_key ?: $through_instance->get_key_name();

        return new HasOneThrough( $instance->new_query(), $this, $through_instance, $first_key, $second_key, $local_key, $second_local_key );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string  $morph_name
     * @param  string|null  $type_column
     * @param  string|null  $id_column
     * @param  string  $local_key
     * @return MorphTo
     */
    public function morph_to( $morph_name, ?string $type_column = null, ?string $id_column = null, string $local_key = 'id' ) {
        $type_column = $type_column ?: $morph_name . '_type';
        $id_column   = $id_column   ?: $morph_name . '_id';

        return new MorphTo( $this->new_query(), $this, $morph_name, $type_column, $id_column, $local_key );
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param  string  $related
     * @param  string  $morph_name
     * @param  string  $type_column
     * @param  string  $id_column
     * @param  string  $local_key
     * @return MorphOne
     */
    public function morph_one( $related, $morph_name, ?string $type_column = null, ?string $id_column = null, string $local_key = 'id' ) {
        /** @var Model $instance */
        $instance    = new $related;
        $type_column = $type_column ?: $morph_name . '_type';
        $id_column   = $id_column   ?: $morph_name . '_id';

        return new MorphOne( $instance->new_query(), $this, $morph_name, $type_column, $id_column, $local_key );
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $morph_name
     * @param  string  $type_column
     * @param  string  $id_column
     * @param  string  $local_key
     * @return MorphMany
     */
    public function morph_many( $related, $morph_name, ?string $type_column = null, ?string $id_column = null, string $local_key = 'id' ) {
        /** @var Model $instance */
        $instance    = new $related;
        $type_column = $type_column ?: $morph_name . '_type';
        $id_column   = $id_column   ?: $morph_name . '_id';

        return new MorphMany( $instance->new_query(), $this, $morph_name, $type_column, $id_column, $local_key );
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $morph_name
     * @param  string  $pivot_table
     * @param  string  $foreign_pivot_key
     * @param  string  $related_pivot_key
     * @param  string  $parent_key
     * @param  string  $related_key
     * @return MorphToMany
     */
    public function morph_to_many( $related, $morph_name, $pivot_table, ?string $foreign_pivot_key = null, ?string $related_pivot_key = null, string $parent_key = 'id', string $related_key = 'id' ) {
        /** @var Model $instance */
        $instance          = new $related;
        $foreign_pivot_key = $foreign_pivot_key ?: $morph_name . '_id';
        $related_pivot_key = $related_pivot_key ?: static::get_foreign_key_from_class( $related );

        return new MorphToMany( $instance->new_query(), $this, $morph_name, $this->get_morph_class(), $pivot_table, $foreign_pivot_key, $related_pivot_key, $parent_key, $related_key );
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $morph_name
     * @param  string  $pivot_table
     * @param  string  $related_pivot_key
     * @param  string  $foreign_pivot_key
     * @param  string  $parent_key
     * @param  string  $related_key
     * @return MorphToMany
     */
    public function morphed_by_many( $related, $morph_name, $pivot_table, ?string $foreign_pivot_key = null, ?string $related_pivot_key = null, string $parent_key = 'id', string $related_key = 'id' ) {
        /** @var Model $instance */
        $instance          = new $related;
        $foreign_pivot_key = $foreign_pivot_key ?: static::get_foreign_key_from_class( static::class );
        $related_pivot_key = $related_pivot_key ?: $morph_name . '_id';

        return new MorphToMany( $instance->new_query(), $this, $morph_name, static::get_morph_class_for( $related ), $pivot_table, $foreign_pivot_key, $related_pivot_key, $parent_key, $related_key, true );
    }

    /**
     * Derive a foreign key name from a fully qualified class name.
     * e.g., 'App\Models\Tag' => 'tag_id', 'App\Models\PostCategory' => 'post_category_id'
     *
     * @param  string  $class
     * @return string
     */
    public static function get_foreign_key_from_class( string $class ): string {
        $parts     = explode( '\\', $class );
        $base_name = end( $parts );

        // Convert PascalCase to snake_case
        $snake = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $base_name ) );

        return $snake . '_id';
    }
}

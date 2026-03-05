# Eloquent: Models

- [Introduction](#introduction)
- [Defining Models](#defining-models)
    - [Eloquent Model Conventions](#eloquent-model-conventions)
- [Default Attribute Values](#default-attribute-values)
- [Retrieving Models](#retrieving-models)
    - [Query Scopes](#query-scopes)
- [Inserting & Updating Models](#inserting-and-updating-models)
    - [Mass Assignment](#mass-assignment)
- [Deleting Models](#deleting-models)
- [Serialization](#serialization)
- [Events & Observers](#events-and-observers)

<a name="introduction"></a>
## Introduction

Eloquent is a powerful ORM that makes it easy to interact with your database. Each database table has a corresponding "Model" that is used to interact with that table. Models allow you to query for data in your tables, as well as insert new records into the table.

To get started, let's create an Eloquent model. Models typically live in the `MyPluginNamespace\App\Models` namespace and extend the `WpMVC\Database\Eloquent\Model` class.

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class Post extends Model
{   
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public static function get_table_name(): string
    {
        return 'posts';
    }

    /**
     * Get the resolver instance.
     *
     * @return Resolver
     */
    public function resolver(): Resolver {
        return new Resolver();
    }
}
```

<a name="eloquent-model-conventions"></a>
### Eloquent Model Conventions

Eloquent will also assume that each table has a primary key column named `id`. If you wish to override this convention (for example, to match the WordPress `ID` column), you may define a protected `$primary_key` property on your model:

```php
class Post extends Model
{
    /**
     * Indicates if the model should handle timestamps.
     *
     * @var bool
     */
    public bool $timestamps = false;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected string $primary_key = 'ID';
}
```

In addition, Eloquent assumes that the primary key is an incrementing integer value. If you wish to use a non-incrementing primary key, you should set the public `$incrementing` property on your model to `false`:

```php
class Post extends Model
{
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
```

#### Timestamps

By default, Eloquent expects `created_at` and `updated_at` columns to exist on your model's corresponding database table. Eloquent automatically sets these columns' values when models are created or updated. If you do not want these columns to be automatically managed by Eloquent, set the `$timestamps` property on your model to `false`:

```php
class Post extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
```

<a name="default-attribute-values"></a>
## Default Attribute Values

By default, a newly instantiated model instance will not contain any attribute values. If you would like to define default values for some of your model's attributes, you may define an `$attributes` property on your model:

```php
class Post extends Model
{
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected array $attributes = [
        'post_status' => 'publish',
    ];
}
```

<a name="retrieving-models"></a>
## Retrieving Models

Once you have created a model and its associated database table, you are ready to start retrieving data from your database. You may think of each Eloquent model as a powerful [query builder](query-builder.md) allowing you to fluently query the database table associated with the model. For example:

```php
use MyPluginNamespace\App\Models\Post;

$posts = Post::query()->where('post_status', 'publish')->get();

foreach ($posts as $post) {
    echo $post->post_title;
}
```

<a name="query-scopes"></a>
### Query Scopes

Scopes allow you to define common sets of constraints that you may easily re-use throughout your application. For example, you may need to frequently retrieve all users that are considered "popular". To define a scope, prefix an Eloquent model method with `scope_`.

Scopes should always return a query builder instance:

```php
class User extends Model
{
    /**
     * Scope a query to only include popular users.
     *
     * @param  \WpMVC\Database\Query\Builder  $query
     * @return \WpMVC\Database\Query\Builder
     */
    public function scope_popular($query)
    {
        return $query->where('votes', '>', 100);
    }
}
```

#### Utilizing A Query Scope

Once the scope has been defined, you may call the scope method when querying the model. However, you should not include the `scope_` prefix when calling the method. You can even chain calls to various scopes:

```php
$users = User::query()->popular()->order_by('created_at')->get();
```

> [!TIP]
> WpMVC supports both `scope_popular` (snake_case) and `scopePopular` (camelCase) naming conventions for defining scopes.


<a name="inserting-and-updating-models"></a>
## Inserting & Updating Models

To create a new record in the database, instantiate a new model instance and set attributes on the model. Then, call the `save` method on the model instance:

```php
use MyPluginNamespace\App\Models\Post;

$post = new Post;
$post->post_title = 'Developing with WpMVC';
$post->save();
```

You may also use the `save` method to update models that already exist in the database:

```php
$post = Post::query()->find(1);
$post->post_title = 'Advanced Eloquent';
$post->save();
```

<a name="mass-assignment"></a>
### Mass Assignment

You may also use the `create` method to "save" a new model using a single PHP statement. The inserted model instance will be returned to you by the method:

```php
use MyPluginNamespace\App\Models\Post;

$post = Post::create([
    'post_title' => 'London to New York',
]);
```

However, before using the `create` method, you will need to specify either a `fillable` or `guarded` property on your model class. These properties are required because all Eloquent models are protected against mass assignment vulnerabilities by default.

```php
class Post extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = ['post_title', 'post_content', 'post_author'];
}
```

#### Global Unguarding

If you are importing data and want to temporarily disable mass assignment protection entirely, use `Model::unguard()`:

```php
use WpMVC\Database\Eloquent\Model;

Model::unguard();

$post = Post::create(['post_title' => 'Imported Post', 'import_id' => 99]);

Model::reguard();
```

<a name="deleting-models"></a>
## Deleting Models

To delete a model, you may call the `delete` method on a model instance:

```php
$post = Post::query()->find(1);
$post->delete();
```



<a name="serialization"></a>
## Serialization

When building APIs, you will often need to convert your models and relationships to arrays or JSON. Eloquent includes convenient methods for making these conversions and controlling which attributes are included in the serialized representation of your models.

#### Converting To Arrays

To convert a model and its loaded [relationships](relationships.md) to an array, you should use the `to_array` method:

```php
$post = Post::query()->with('meta')->first();

return $post->to_array();
```

#### Hiding Attributes From JSON

Sometimes you may wish to limit the attributes, such as passwords, that are included in your model's array or JSON representation. To do so, add a `$hidden` property to your model:

```php
class User extends Model
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected array $hidden = ['user_pass'];
}
```

Alternatively, you may use the `visible` property to define an "allow list" of attributes that should be included in your model's array and JSON representation:

```php
class User extends Model
{
    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected array $visible = ['first_name', 'last_name'];
}
```

#### Appending Values To JSON

Occasionally, when converting models to an array or JSON, you may wish to add attributes that do not have a corresponding column in your database. To do so, first define an [accessor](mutators-and-casting.md) for the value:

```php
public function get_is_admin_attribute(): bool
{
    return $this->attributes['admin'] === 'yes';
}
```

After creating the accessor, add the attribute name to the `appends` property on the model:

```php
class User extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected array $appends = ['is_admin'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'user_status' => 'int',
        'user_registered' => 'datetime',
    ];
}
```

Once the attribute has been added to the `appends` list, it will be included in both the model's array and JSON representations.

<a name="events-and-observers"></a>
## Events & Observers

### Events

Eloquent models fire several events, allowing you to hook into the following moments in a model's lifecycle: `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`.

#### WordPress Integration

Every Eloquent event is also broadcast as a WordPress hook. This allows you to listen for model changes from anywhere in your WordPress plugin.

- **Hook Prefix**: The default prefix is `wpmvc`, but this can be changed in your `app.php` config.
- **Global Hooks**: `{prefix}_model_{event}` (e.g., `wpmvc_model_saved`)
- **Table Specific Hooks**: `{prefix}_model_{event}_{table_name}` (e.g., `wpmvc_model_saving_users`)

##### Halting Operations

When an model event is broadcast with an "ing" suffix (e.g., `saving`, `creating`), you may return `false` from the filter to cancel the database operation. These events use `apply_filters`.

```php
// Prevent posts with empty titles from being saved
add_filter('wpmvc_model_saving_posts', function ($halt, $post) {
    if (empty($post->post_title)) {
        return false; // Cancels the save operation
    }
    return $halt;
}, 10, 2);
```

> [!NOTE]
> The `retrieved` event is fired when an existing model is retrieved from the database.
```

Completed events (e.g., `saved`, `created`, `retrieved`) use `do_action` and cannot be halted.

```php
add_action('wpmvc_model_created_posts', function ($post) {
    // Perform post-creation tasks...
});
```

### Observers

If you are listening for many events on a given model, you may use observers to group all of your listeners into a single class. Observers classes have method names which reflect the Eloquent events you wish to listen for:

```php
class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \MyPluginNamespace\App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        // ...
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \MyPluginNamespace\App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        // ...
    }
}
```

To register an observer, use the `observe` method on the model you wish to observe:

```php
use MyPluginNamespace\App\Models\User;
use MyPluginNamespace\App\Observers\UserObserver;

User::observe(UserObserver::class);
```

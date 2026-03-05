# Eloquent: Relationships

- [Introduction](#introduction)
- [Defining Relationships](#defining-relationships)
    - [One To One](#one-to-one)
    - [One To Many](#one-to-many)
    - [Many To Many](#many-to-many)
        - [Pivot Attributes](#pivot-attributes)
    - [Has One Through](#has-one-through)
    - [Has Many Through](#has-many-through)
    - [Default Relationship Constraints](#default-relationship-constraints)
- [Polymorphic Relationships](#polymorphic-relationships)
    - [One To One (Polymorphic)](#one-to-one-polymorphic)
    - [One To Many (Polymorphic)](#one-to-many-polymorphic)
    - [Many To Many (Polymorphic)](#many-to-many-polymorphic)
- [Querying Relations](#querying-relations)
    - [Relationship Methods Vs. Dynamic Properties](#relationship-methods-vs-dynamic-properties)
    - [Querying Relationship Existence](#querying-relationship-existence)
    - [Querying Relationship Absence](#querying-relationship-absence)
- [Aggregating Related Models](#aggregating-related-models)
    - [Counting Related Models](#counting-related-models)
- [Eager Loading](#eager-loading)
    - [Constraining Eager Loads](#constraining-eager-loads)
    - [Nested Eager Loading](#nested-eager-loading)
- [Manual Relationship Management](#manual-relationship-management)

<a name="introduction"></a>
## Introduction

Database tables are often related to one another. For example, a store may have many products, or a product could be related to the customer who ordered it. Eloquent makes managing and working with these relationships easy, and supports several common relationships.

<a name="defining-relationships"></a>
## Defining Relationships

Eloquent relationships are defined as methods on your Eloquent model classes. Since relationships also serve as powerful [query builders](query-builder.md), defining relationships as methods provides powerful method chaining and querying capabilities. For example, we may chain additional query constraints on this `orders` relationship:

```php
$customers = Customer::query()->where('status', 'active')->get();
```

<a name="one-to-one"></a>
### One To One

A one-to-one relationship is a very basic type of database relationship. For example, a `Customer` model might be associated with one `Profile` model. To define this relationship, we will place a `profile` method on the `Customer` model. The `profile` method should call the `has_one` method and return its result:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    /**
     * Get the customer's profile.
     */
    public function profile(): HasOne
    {
        return $this->has_one(Profile::class, 'customer_id', 'id');
    }
}
```

The first argument passed to the `has_one` method is the name of the related model class. Once the relationship is defined, we may retrieve the related record using Eloquent's dynamic properties:

```php
$profile = Customer::query()->find(1)->profile;
```

Eloquent determines the foreign key of the relationship based on the parent model name. In this case, the `Profile` model is automatically assumed to have a `customer_id` foreign key. If you wish to override this convention, you may pass the second and third arguments to the `has_one` method:

```php
return $this->has_one(Profile::class, 'customer_id', 'id');
```

#### Defining The Inverse Of The Relationship

Now that we can access the `Profile` model from our `Customer` model, let's define a relationship on the `Profile` model that will let us access the customer that owns the profile. We can define the inverse of a `has_one` relationship using the `belongs_to` method:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    /**
     * Get the customer that owns the profile.
     */
    public function customer(): BelongsTo
    {
        return $this->belongs_to(Customer::class, 'customer_id', 'id');
    }
}
```

<a name="one-to-many"></a>
### One To Many

A one-to-many relationship is used to define relationships where a single model is the parent to one or more child models. For example, a store may have many products. Like all other Eloquent relationships, one-to-many relationships are defined by placing a function on your Eloquent model:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /**
     * Get the products for the store.
     */
    public function products(): HasMany
    {
        return $this->has_many(Product::class, 'store_id', 'id');
    }

    /**
     * Get the owner of the store.
     */
    public function owner(): BelongsTo
    {
        return $this->belongs_to(Customer::class, 'customer_id', 'id');
    }
}
```

Remember, Eloquent will automatically determine the proper foreign key column for the `Product` model. By convention, Eloquent will take the "snake case" name of the parent model and suffix it with `_id`. So, in this example, Eloquent will assume the foreign key column on the `Product` model is `store_id`.

Once the relationship method has been defined, we can access the collection of products by accessing the `products` property:

```php
use MyPluginNamespace\App\Models\Store;

$products = Store::query()->find(1)->products;

foreach ($products as $product) {
    // ...
}
```

Of course, since all relationships also serve as query builders, you may add further constraints to the relationship query:

```php
$product = Store::query()->find(1)->products()
                    ->where('status', 'in_stock')
                    ->first();
```

<a name="has-one-of-many"></a>
#### Has One Of Many

Sometimes a relationship may have many related models, but you want to easily retrieve the "latest" or "oldest" related model of the relationship. For example, a `Customer` model may be related to many `Order` models, but you may want to define a one-to-one relationship representing the customer's latest order:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasOne;
use WpMVC\Database\Eloquent\Relations\HasOneOfMany;

class Customer extends Model
{
    /**
     * Get the customer's latest order using has_one_of_many.
     */
    public function latest_order(): HasOneOfMany
    {
        return $this->has_one_of_many(Order::class, 'customer_id', 'id', 'id', 'desc');
    }

    /**
     * Alternatively, you can chain latest() on a normal has_one.
     */
    public function latest_order_alt(): HasOne
    {
        return $this->has_one(Order::class, 'customer_id', 'id')->latest('id');
    }
}
```

<a name="many-to-many"></a>
### Many To Many

Many-to-many relations are slightly more complicated than `has_one` and `has_many` relationships. An example of such a relationship is a product with many categories, where the categories are also shared by other products in the system. For example, a product may be assigned the category of "Home" and "Garden"; however, those categories may also be assigned to other products.

<a name="pivot-attributes"></a>
#### Pivot Attributes

When working with many-to-many relationships, you often need to access the data stored in the intermediate (pivot) table. Eloquent automatically prefixes the foreign and local pivot keys with `pivot_` (e.g., `pivot_product_id`).

However, other columns in your pivot table must be explicitly selected in your relationship definition if you wish to access them with a `pivot_` prefix. For example, if your `category_product` table has a `position` column:

```php
public function categories(): BelongsToMany
{
    return $this->belongs_to_many(Category::class, 'category_product')
                ->add_select('category_product.position as pivot_position');
}
```

Now you can access it like so:

```php
$product = Product::query()->find(1);

foreach ($product->categories as $category) {
    echo $category->pivot_position;
}
```

In this example, the `Product` model is related to `Category` via the `category_product` table.

To define this relationship, three database tables are needed: `products`, `categories`, and `category_product`. The `category_product` table contains `product_id` and `category_id` columns.

Many-to-many relationships are defined by returning the result of the `belongs_to_many` method:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    /**
     * Get the categories for the product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongs_to_many(Category::class, 'category_product', 'product_id', 'category_id', 'id', 'id');
    }
}
```

Once the relationship is defined, you may access the product's categories using the `categories` dynamic property:

```php
use MyPluginNamespace\App\Models\Product;

$product = Product::query()->find(1);

foreach ($product->categories as $category) {
    // ...
}
```

<a name="has-one-through"></a>
### Has One Through

The "has-one-through" relationship defines a one-to-one relationship with another model. However, this relationship indicates that the declaring model can be matched with one instance of another model by proceeding *through* a third model.

For example, you might want to access a supplier's latest review through their products. While the supplier and the review have no direct relationship within the database, the supplier can access the review *through* the product. Let's look at the tables necessary to define this relationship:

```php
suppliers
    id - integer
    name - string

products
    id - integer
    name - string
    supplier_id - integer

reviews
    id - integer
    content - text
    product_id - integer
```

Now that we have examined the table structure for the relationship, let's define the relationship on the `Supplier` model:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasOneThrough;

/**
 * We'll use the supplier's latest review as an example of Has One Through.
 */
class Supplier extends Model
{
    /**
     * Get the supplier's latest review through their products.
     */
    public function latest_review(): HasOneThrough
    {
        return $this->has_one_through(Review::class, Product::class, 'supplier_id', 'product_id');
    }
}
```

<a name="has-many-through"></a>
### Has Many Through

The "has-many-through" relationship provides a convenient way to access distant relations via an intermediate relation. For example, a `Supplier` model might access many `Review` models through an intermediate `Product` model. This is useful for retrieving all reviews made on any of a supplier's products:

```php
suppliers
    id - integer
    name - string

products
    id - integer
    supplier_id - integer
    name - string

reviews
    id - integer
    product_id - integer
    content - string
```

Though the `reviews` table does not contain a `supplier_id` column, the `has_many_through` relation provides access to a supplier's reviews via `$supplier->reviews_through_products`. To define this relationship, we will pass the "final" model we wish to access as the first argument and the "intermediate" model as the second argument:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasManyThrough;

class Supplier extends Model
{
    /**
     * Get all of the reviews for the supplier's products.
     */
    public function reviews_through_products(): HasManyThrough
    {
        return $this->has_many_through(Review::class, Product::class, 'supplier_id', 'product_id');
    }
}
```

#### Polymorphic Through Relationships

If the "distant" (final) table in your through relationship is polymorphic, you may use the `where_morph_type` method to constrain the results by a specific morph type. For example, if your `Review` model can belong to both `Product` and `Service`, and you want all product reviews for a supplier:

```php
public function product_reviews(): HasManyThrough
{
    // In this example, the 'reviews' table uses 'reviewable_id' and 'reviewable_type'
    return $this->has_many_through(Review::class, Product::class, 'supplier_id', 'reviewable_id')
                ->where_morph_type('reviewable_type', 'product');
}
```

<a name="default-relationship-constraints"></a>
### Default Relationship Constraints

Since relationship methods also serve as query builders, you may add additional constraints to the relationship definition itself. For example, if you frequently want to retrieve only the "featured" products for a store, you can define a relationship that includes this constraint:

```php
public function featured_products(): HasMany
{
    return $this->has_many(Product::class, 'store_id', 'id')
                ->where('is_featured', true)
                ->order_by('created_at', 'desc');
}
```

Now, when you access the `featured_products` property, the constraint will be applied automatically:

```php
$store = Store::query()->find(1);

foreach ($store->featured_products as $product) {
    // These products are already filtered and ordered...
}
```

You can even use these constrained relationships with eager loading:

```php
$stores = Store::with('featured_products')->get();
```

<a name="polymorphic-relationships"></a>
## Polymorphic Relationships

A polymorphic relationship allows the child model to belong to more than one type of model using a single association. For example, imagine you are building an application where both products and stores can have multiple images.

<a name="one-to-one-polymorphic"></a>
### One To One (Polymorphic)

A one-to-one polymorphic relation is similar to a simple one-to-one relation; however, the child model can belong to more than one type of model using a single association. For example, a `Product` and a `Store` may share a polymorphic relation to an `Image` model.

```php
products
    id - integer
    name - string

stores
    id - integer
    name - string

images
    id - integer
    url - string
    imageable_id - integer
    imageable_type - string
```

Take note of the `imageable_id` and `imageable_type` columns on the `images` table. The `imageable_id` column will contain the ID value of the product or store, while the `imageable_type` column will contain the class name of the parent model.

> [!IMPORTANT]
> When creating polymorphic models via a relationship (e.g., using `create()` or `make()`), you must manually set the morph type column, as the current implementation does not automatically populate it for one-to-one or one-to-many polymorphic relations.

```php
$product = Product::query()->find(1);

$product->image()->create([
    'url'            => 'https://example.com/image.jpg',
    'imageable_type' => $product->get_morph_class(),
]);
```

#### Model Structure

Next, let's examine the model definitions needed to build this relationship:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    /**
     * Get the parent imageable model (product or store).
     */
    public function imageable(): MorphTo
    {
        return $this->morph_to('imageable', null, null, 'id');
    }
}

class Product extends Model
{
    /**
     * Get the product's image.
     */
    public function image(): MorphOne
    {
        return $this->morph_one(Image::class, 'imageable', null, null, 'id');
    }
}

class Store extends Model
{
    /**
     * Get the store's image.
     */
    public function image(): MorphOne
    {
        return $this->morph_one(Image::class, 'imageable', null, null, 'id');
    }
}
```

<a name="one-to-many-polymorphic"></a>
### One To Many (Polymorphic)

A one-to-many polymorphic relation is similar to a simple one-to-many relation; however, the child model can belong to more than one type of model using a single association. For example, imagine users of your application can leave "reviews" on both products and stores:

```php
products
    id - integer
    name - string

stores
    id - integer
    name - string

reviews
    id - integer
    content - text
    reviewable_id - integer
    reviewable_type - string
```

#### Model Structure

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    /**
     * Get all of the product's reviews.
     */
    public function reviews(): MorphMany
    {
        return $this->morph_many(Review::class, 'reviewable', null, null, 'id');
    }
}

class Store extends Model
{
    /**
     * Get all of the store's reviews.
     */
    public function reviews(): MorphMany
    {
        return $this->morph_many(Review::class, 'reviewable', null, null, 'id');
    }
}
```

<a name="many-to-many-polymorphic"></a>
### Many To Many (Polymorphic)

Many-to-many polymorphic relations are slightly more complicated than `morph_one` and `morph_many` relationships. For example, a `Product` and `Store` model could share a polymorphic relation to a `Tag` model. Using a many-to-many polymorphic relation allows you to have a single list of unique tags that are shared across products and stores.

```php
products
    id - integer
    name - string

stores
    id - integer
    name - string

tags
    id - integer
    name - string

taggables
    tag_id - integer
    taggable_id - integer
    taggable_type - string
```

#### Model Structure

Next, we're ready to define the relationships on the models. The `Product` and `Store` models will both have a `tags` method that calls the `morph_to_many` method:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\MorphToMany;

class Product extends Model
{
    /**
     * Get all of the tags for the product.
     */
    public function tags(): MorphToMany
    {
        return $this->morph_to_many(Tag::class, 'taggable', 'taggables', null, null, 'id', 'id');
    }
}
```

On the `Tag` model, you should define a method for each of its related models. In this example, we will define a `products` method and a `stores` method. These methods should both return the result of the `morphed_by_many` method:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    /**
     * Get all of the products that are assigned this tag.
     */
    public function products(): MorphToMany
    {
        return $this->morphed_by_many(Product::class, 'taggable', 'taggables', null, null, 'id', 'id');
    }

    /**
     * Get all of the stores that are assigned this tag.
     */
    public function stores(): MorphToMany
    {
        return $this->morphed_by_many(Store::class, 'taggable', 'taggables', null, null, 'id', 'id');
    }
}
```

<a name="custom-polymorphic-types"></a>
#### Custom Polymorphic Types

By default, Eloquent will use the fully qualified class name to store the "type" of the related model. For example, given the one-to-many example above where a `Review` may belong to a `Product` or a `Store`, the `reviewable_type` would be either `MyPluginNamespace\App\Models\Product` or `MyPluginNamespace\App\Models\Store`, respectively. However, you may wish to decouple your database from your application's internal structure.

You may define a "morph map" to instruct Eloquent to use a custom name for each model instead of the class name:

```php
use WpMVC\Database\Eloquent\Model;

Model::morph_map([
    'product' => 'MyPluginNamespace\App\Models\Product',
    'store' => 'MyPluginNamespace\App\Models\Store',
]);
```

You may register the `morph_map` in your plugin's boot method or a service provider.

<a name="querying-relations"></a>
## Querying Relations

<a name="relationship-methods-vs-dynamic-properties"></a>
### Relationship Methods Vs. Dynamic Properties

If you do not need to add additional constraints to an Eloquent relationship query, you may access the relationship as if it were a property. For example, continuing to use our `Customer` and `Order` models:

```php
$customer = Customer::query()->find(1);

foreach ($customer->orders as $order) {
    // ...
}
```

Dynamic properties are "lazy loading", meaning they will only load their relationship data when you actually access them. Because of this, developers often use [eager loading](#eager-loading) to pre-load relationships they know will be accessed after loading the model. Eager loading provides a significant reduction in SQL queries that must be executed to load a model's relations.

<a name="querying-relationship-existence"></a>
### Querying Relationship Existence

When retrieving model records, you may wish to limit your results based on the existence of a relationship. For example, imagine you want to retrieve all stores that have at least one product. To do so, you may pass the name of the relationship to the `has` method:

```php
use MyPluginNamespace\App\Models\Store;

$stores = Store::has('products')->get();
```

You may also specify an operator and count to further customize the query:

```php
$stores = Store::query()->where_has('products', function ($query) {
    $query->where('price', '>', 100);
})->get();
```

You may also use "dot" notation to query relationship existence for nested relationships. For example, you may retrieve all stores that have at least one product that has at least one review:

```php
$stores = Store::has('products.reviews')->get();
```

You can also use dot notation with the `where_has` method to add constraints to those nested relations:

```php
$stores = Store::query()->where_has('products.reviews', function ($query) {
    $query->where('rating', 5);
})->get();
```

<a name="querying-relationship-absence"></a>
### Querying Relationship Absence

When retrieving model records, you may wish to limit your results based on the absence of a relationship. For example, you may retrieve all stores that do **not** have any products using the `doesnt_have` method:

```php
$stores = Store::doesnt_have('products')->get();
```

If you need even more power, you may use the `where_doesnt_have` method to put "where" constraints on your `doesnt_have` queries:

```php
$stores = Store::where_doesnt_have('products', function ($query) {
    $query->where('price', '>', 100);
})->get();
```

You may also use "or" variants such as `or_has`, `or_where_has`, `or_doesnt_have`, and `or_where_doesnt_have` to combine relationship checks:

```php
// Retrieve stores that have products OR have at least one review
$stores = Store::has('products')->or_has('reviews')->get();
```

<a name="aggregating-related-models"></a>
## Aggregating Related Models

<a name="counting-related-models"></a>
### Counting Related Models

Sometimes you may want to count the number of related models for a given relationship without actually loading the models. To accomplish this, you may use the `with_count` method, which will place a `{relation}_count` column on your resulting models:

```php
use MyPluginNamespace\App\Models\Store;

$stores = Store::with_count('products')->get();

foreach ($stores as $store) {
    echo $store->products_count;
}
```

You may also "count" into multiple relationships as well as add constraints to the queries:

```php
$stores = Store::with_count(['products'])->get();

echo $stores[0]->products_count;
```

#### Other Aggregates

In addition to `with_count`, Eloquent provides `with_sum`, `with_avg`, `with_min`, and `with_max` aggregate methods. These methods will place a `{relation}_{function}_{column}` attribute on your resulting models:

```php
$customers = Customer::with_sum('orders', 'total_price')->get();

foreach ($customers as $customer) {
    echo $customer->orders_sum_total_price;
}
```

#### Aggregate Aliasing

If you wish to specify a custom name for the aggregate result, you may use the `as` keyword:

```php
$stores = Store::with_count('products as total_products')->get();

echo $stores[0]->total_products;
```

<a name="eager-loading"></a>
## Eager Loading

When accessing Eloquent relationships as properties, the relationship data is "lazy loaded". This means the relationship data is not actually loaded until you first access the property. However, Eloquent can "eager load" relationships at the time you query the parent model. Eager loading alleviates the N + 1 query problem.

To eager load a relationship, use the `with` method:

```php
use MyPluginNamespace\App\Models\Store;

$stores = Store::query()->with('owner')->get();

foreach ($stores as $store) {
    echo $store->owner->name;
}
```

You may eager load multiple relationships in a single operation:

```php
$stores = Store::query()->with('owner', 'products')->get();
```

<a name="nested-eager-loading"></a>
### Nested Eager Loading

To eager load nested relationships, you may use "dot" syntax. For example, let's eager load all of the store's owner and all of the owner's profile in one Eloquent statement:

```php
$stores = Store::query()->with('owner.profile')->get();
```

<a name="constraining-eager-loads"></a>
### Constraining Eager Loads

Sometimes you may wish to eager load a relationship but also specify additional query constraints for the eager loading query:

```php
$customers = Customer::query()->with(['orders' => function ($query) {
    $query->where('status', 'completed');
}])->get();
```
In this example, Eloquent will only eager load orders that have a status of `completed`. Of course, you may call other [query builder](query-builder.md) methods to further customize the eager loading operation:

```php
$customers = Customer::query()->with(['orders' => function ($query) {
    $query->order_by('created_at', 'desc');
}])->get();
```

<a name="inserting-and-updating-related-models"></a>
## Inserting & Updating Related Models

### The `make` and `create` Methods

Eloquent provides methods for creating new related models. For example, you may use the `make` method to create a new instance of a related model with the foreign key already set, but without persisting it to the database:

```php
$store = Store::query()->find(1);

$product = $store->products()->make([
    'name' => 'My new product',
]);
```

The `create` method works similarly but will actually persist the model to the database:

```php
$store = Store::query()->find(1);

$product = $store->products()->create([
    'name' => 'My new product',
]);
```

### Many To Many Relationships

#### Attaching / Detaching

The many-to-many relationship also provides an `attach` method to make associating models easy. For example, let's imagine a product can belong to multiple categories and a category can contain multiple products. To attach a category to a product by inserting a record in the relationship's intermediate table:

```php
$product = Product::query()->find(1);

$product->categories()->attach($category_id);
```

When attaching a relationship to a model, you may also pass an array of additional data to be inserted into the intermediate table:

```php
$product->categories()->attach($category_id, ['position' => 1]);
```

<a name="manual-relationship-management"></a>
## Manual Relationship Management

Sometimes you may want to manually set or check a relationship on a model instance without querying the database.

#### Setting & Getting Relations

You may use the `set_relation` and `get_relation` methods to manually manage the loaded relationships for a model:

```php
$customer->set_relation('orders', $orders_collection);

$loaded_orders = $customer->get_relation('orders');
```

#### Checking & Unsetting Relations

The `relation_loaded` and `unset_relation` methods are useful for checking if a relationship has been loaded or discarding a loaded relationship:

```php
if ($customer->relation_loaded('orders')) {
    $customer->unset_relation('orders');
}
```

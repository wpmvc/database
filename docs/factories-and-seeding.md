# Database: Factories & Seeding

- [Introduction](#introduction)
- [Database Factories](#database-factories)
    - [Defining Factories](#defining-factories)
    - [Factory States](#factory-states)
    - [Factory Hooks](#factory-hooks)
- [Creating Models Using Factories](#creating-models-using-factories)
    - [Instantiating Models](#instantiating-models)
    - [Persisting Models](#persisting-models)
    - [Sequences](#sequences)
- [Factory Relationships](#factory-relationships)
    - [Has Many Relationships](#has-many-relationships)
    - [Belongs To Relationships](#belongs-to-relationships)
    - [Polymorphic Relationships](#polymorphic-relationships)
    - [Recycling Models](#recycling-models)
- [Database Seeding](#database-seeding)
    - [Writing Seeders](#writing-seeders)
    - [Running Seeders](#running-seeders)

<a name="introduction"></a>
## Introduction

When testing your application or seeding your database, you may need to insert a few records into your database. Instead of manually specifying the value of each column, WpMVC allows you to define a set of default attributes for each of your Eloquent models using database factories.

To learn how to write seeders and use factories to hydrate your database with records, let's take a look at the following documentation.

<a name="database-factories"></a>
## Database Factories

<a name="defining-factories"></a>
To define a factory, create a class that extends `WpMVC\Database\Eloquent\Factory` and define a `definition` method. The `definition` method should return the default set of attribute values that should be applied when creating a model using the factory.

Through the `faker()` method, factories have access to the `FakeData` generator, which allows you to conveniently generate various kinds of random data for testing and seeding:

```php
namespace MyPluginNamespace\Database\Factories;

use WpMVC\Database\Eloquent\Factory;

class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker()->first_name(),
            'email' => $this->faker()->unique()->email(),
            'status' => 'active',
        ];
    }
}
```

<a name="factory-states"></a>
State manipulation methods allow you to define discrete modifications that can be applied to your database factories in any combination. For example, your `Customer` model might have a `suspended` state that modifies one of its default attribute values.

You may define your state transformations using the `state` method. The `state` method accepts a closure which will receive the array of raw attributes defined for the factory:

```php
/**
 * Indicate that the customer is suspended.
 *
 * @return \WpMVC\Database\Eloquent\Factory
 */
public function suspended(): Factory
{
    return $this->state(function (array $attributes) {
        return [
            'status' => 'suspended',
        ];
    });
}
```

> [!TIP]
> Eloquent factories also support "magic states." If you call a method on a factory that is not explicitly defined, it will automatically apply a state where the attribute matching the method name is set to `true`. For example, `$customer->active()` is equivalent to `$customer->state(['active' => true])`.

<a name="factory-hooks"></a>
### Factory Hooks

Factory hooks allow you to perform additional tasks after making or creating a model. You should register these hooks by overriding the `configure` method within your factory class and calling the `after_making` or `after_creating` methods:

```php
namespace MyPluginNamespace\Database\Factories;

use MyPluginNamespace\App\Models\Customer;
use WpMVC\Database\Eloquent\Factory;

class CustomerFactory extends Factory
{
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->after_making(function (Customer $customer) {
            // ...
        })->after_creating(function (Customer $customer) {
            // ...
        });
    }
}
```

<a name="creating-models-using-factories"></a>
## Creating Models Using Factories

<a name="instantiating-models"></a>
Once you have defined your factories, you may use the static `factory` method provided to your models by the `WpMVC\Database\Eloquent\Concerns\HasFactory` trait to instantiate a factory instance for that model. Let's look at a few examples of creating models. First, we'll use the `make` method to create models without persisting them to the database:

```php
use MyPluginNamespace\App\Models\Customer;

$customer = Customer::factory()->make();
```

You may create a collection of many models using the `count` method:

```php
$customers = Customer::factory()->count(3)->make();
```

#### Overriding Attributes

If you would like to override some of the default values of your models, you may pass an array of values to the `make` method. Only the specified attributes will be replaced while the rest of the attributes remain set to their default values as defined by the factory:

```php
$customer = Customer::factory()->make([
    'first_name' => 'Abigail',
]);
```

<a name="persisting-models"></a>
The `create` method instantiates model instances and persists them to the database using Eloquent's `save` method:

```php
use MyPluginNamespace\App\Models\Customer;

// Create a single Customer instance...
$customer = Customer::factory()->create();

// Create three Customer instances...
$customers = Customer::factory()->count(3)->create();
```

You may override attributes on the `create` method by passing an array to the method:

```php
$customer = Customer::factory()->create([
    'first_name' => 'Abigail',
]);
```

<a name="sequences"></a>
Sometimes you may wish to alternate the value of a given model attribute for each created model. You may accomplish this by defining a state transformation as a sequence. For example, you may wish to alternate the value of a `status` column between `active` and `pending` for each created customer:

```php
use MyPluginNamespace\App\Models\Customer;
use WpMVC\Database\Eloquent\Sequence;

$customers = Customer::factory()
                ->count(10)
                ->state(new Sequence(
                    ['status' => 'active'],
                    ['status' => 'pending'],
                ))
                ->create();
```

<a name="factory-relationships"></a>
## Factory Relationships

<a name="has-many-relationships"></a>
Next, let's explore building Eloquent relationship associations using factories. First, let's imagine our `Customer` model has a `products` relationship. To create a customer with three products, we can use the `has` method. The `has` method accepts a factory instance:

```php
use MyPluginNamespace\App\Models\Product;
use MyPluginNamespace\App\Models\Customer;

$customer = Customer::factory()
            ->has(Product::factory()->count(3))
            ->create();
```

For convenience, you may also use magic methods to define factory relationships. For example, because our `Customer` model defines a `products` relationship, we can do the following:

```php
$customer = Customer::factory()
            ->has_products(3)
            ->create();
```

<a name="belongs-to-relationships"></a>
### Belongs To Relationships

Now that we have explored how to build "has many" relationships using factories, let's explore the inverse of the relationship. The `for` method may be used to define the parent model that factory created models belong to. For example, we can create three review model instances that belong to a single product:

```php
use MyPluginNamespace\App\Models\Product;
use MyPluginNamespace\App\Models\Review;

$reviews = Review::factory()
                ->count(3)
                ->for(Product::factory()->create())
                ->create();
```

Like the `has` method, you may use magic methods to define "belongs to" relationships:

```php
$reviews = Review::factory()
                ->count(3)
                ->for_product(['name' => 'Jessica'])
                ->create();
```

<a name="polymorphic-relationships"></a>
### Polymorphic Relationships

Polymorphic relationships may also be created using factories. For example, if a `Review` model has a polymorphic `reviewable` relationship, you may use the `for` method to specify the parent model:

```php
use MyPluginNamespace\App\Models\Product;
use MyPluginNamespace\App\Models\Review;

$product = Product::factory()->create();

$review = Review::factory()
            ->for($product, 'reviewable')
            ->create();
```

<a name="recycling-models"></a>
### Recycling Models

When creating models that share a common relationship, you may use the `recycle` method to ensure that all generated models use the same instance of a related model. This prevents the factory from creating a new related model for every instance:

```php
use MyPluginNamespace\App\Models\Customer;
use MyPluginNamespace\App\Models\Product;

$customer = Customer::factory()->create();

$products = Product::factory()
                ->count(10)
                ->recycle($customer)
                ->create();
```

<a name="database-seeding"></a>
## Database Seeding

WpMVC includes the ability to seed your database with test data using seed classes. All seed classes are stored in the `MyPluginNamespace\App\Seeders` namespace. By default, a `DatabaseSeeder` class is defined for you. From this class, you may use the `call` method to run other seed classes, allowing you to control the seeding order.

<a name="writing-seeders"></a>
### Writing Seeders

To write a seeder, define a `run` method on your seeder class. Within the `run` method, you may insert data into your database however you wish. You may use [Eloquent model factories](#database-factories) or you may use the [query builder](query-builder.md) to manually insert data.

As an example, let's modify the default `DatabaseSeeder` class and add a database insertion statement to the `run` method using the `Customer` model:

```php
namespace MyPluginNamespace\Database\Seeders;

use WpMVC\Database\Seeder;
use MyPluginNamespace\App\Models\Customer;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Customer::query()->insert([
            'first_name' => 'Admin',
            'email' => 'admin@example.com',
        ]);
    }
}
```

Within the `run` method, you may use the `call` method to execute additional seed classes. Using the `call` method allows you to break up your database seeding into multiple files so that no single seeder class becomes too large. The `call` method accepts an array of seeder classes that should be executed:

```php
/**
 * Seed the application's database.
 *
 * @return void
 */
public function run()
{
    $this->call([
        CustomerSeeder::class,
        ProductSeeder::class,
        ReviewSeeder::class,
    ]);
}
```

<a name="running-seeders"></a>
### Running Seeders

You may execute your seeders using the static `run_seeder` method on the `WpMVC\Database\Seeder` class:

```php
use WpMVC\Database\Seeder;
use MyPluginNamespace\Database\Seeders\DatabaseSeeder;

Seeder::run_seeder(DatabaseSeeder::class);
```

By default, the `run_seeder` method will execute the `run` method on the given seeder class. 

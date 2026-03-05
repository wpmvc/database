# Eloquent: Mutators & Casting

- [Introduction](#introduction)
- [Accessors & Mutators](#accessors-and-mutators)
    - [Defining An Accessor](#defining-an-accessor)
    - [Defining A Mutator](#defining-a-mutator)
- [Attribute Casting](#attribute-casting)
    - [Array & JSON Casting](#array-and-json-casting)
    - [Date Casting](#date-casting)

<a name="introduction"></a>
## Introduction

Accessors and mutators allow you to format Eloquent attribute values when you retrieve or set them on model instances. For example, you may want to format a date attribute to a specific format or capitalize a string when it is retrieved.

In addition to custom accessors and mutators, Eloquent can also automatically cast date fields to `DateTime` instances or even cast JSON fields to arrays.

<a name="accessors-and-mutators"></a>
## Accessors & Mutators

<a name="defining-an-accessor"></a>
### Defining An Accessor

To define an accessor, create a `get_{attribute}_attribute` method on your model where `{attribute}` is the "snake case" name of the column you wish to access. In this example, we'll define an accessor for the `first_name` attribute. The accessor will automatically be called by Eloquent when attempting to retrieve the value of the `first_name` attribute:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;

class Customer extends Model
{
    /**
     * Get the customer's first name.
     *
     * @param  string  $value
     * @return string
     */
    public function get_first_name_attribute($value)
    {
        return ucfirst($value);
    }
}
```

As you can see, the original value of the column is passed to the accessor, allowing you to manipulate and return the value. To access the value of the accessor, you may simply access the `first_name` attribute on a model instance:

```php
use MyPluginNamespace\App\Models\Customer;

$customer = Customer::query()->find(1);

$first_name = $customer->first_name;
```

<a name="defining-a-mutator"></a>
### Defining A Mutator

To define a mutator, define a `set_{attribute}_attribute` method on your model where `{attribute}` is the "snake case" name of the column you wish to access. This mutator will be automatically called when we attempt to set the value of the `first_name` attribute on the model:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;

class Customer extends Model
{
    /**
     * Set the customer's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function set_first_name_attribute($value)
    {
        $this->attributes['first_name'] = strtolower($value);
    }
}
```

The mutator will receive the value that is being set on the attribute, allowing you to manipulate the value and set the manipulated value on the Eloquent model's internal `$attributes` property. To use our mutator, we only need to set the `first_name` attribute on an Eloquent model:

```php
use MyPluginNamespace\App\Models\Customer;

$customer = Customer::query()->find(1);

$customer->first_name = 'Sally';
```

In this example, the `set_first_name_attribute` function will be called with the value `Sally`. The mutator will then apply the `strtolower` function to the name and set its resulting value in the internal `$attributes` array.

<a name="attribute-casting"></a>
## Attribute Casting

Attribute casting provides functionality similar to accessors and mutators without requiring you to define any additional methods on your model. Instead, your model's `$casts` property should be an array where the key is the name of the attribute being cast and the value is the type you wish to cast the column to.

The supported cast types are:
- `int`, `integer`
- `real`, `float`, `double`
- `decimal:<precision>` (e.g., `decimal:2`)
- `string`
- `bool`, `boolean`
- `object` (json_decode to stdClass)
- `array`, `json` (json_decode to array)
- `date`, `datetime`
- `date:<format>`, `datetime:<format>` (e.g., `datetime:Y-m-d`)

For example, let's cast the `is_admin` attribute, which is stored in our database as an integer (`0` or `1`) to a boolean value:

```php
namespace MyPluginNamespace\App\Models;

use WpMVC\Database\Eloquent\Model;

class Customer extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'status' => 'int',
        'is_admin' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
}
```

Now the `status` attribute will always be cast to an integer when you access it:

```php
$customer = Customer::query()->find(1);

if ($customer->status === 0) {
    // ...
}
```

<a name="array-and-json-casting"></a>
### Array & JSON Casting

The `array` cast is particularly useful when working with columns that are stored as serialized JSON. For example, if your database has a `JSON` or `TEXT` field type that contains serialized JSON, adding the `array` or `json` cast to that attribute will automatically deserialize the attribute to a PHP array when you access it on your Eloquent model:

```php
class Order extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'meta' => 'json',
    ];
}
```

Once the cast is defined, you may access the `meta` attribute and it will automatically be deserialized from JSON into a PHP array. When you set the value of the `meta` attribute, the given array will automatically be serialized back into JSON for storage:

```php
$order = Order::query()->find(1);

$meta = $order->meta;

$meta['source'] = 'web';

$order->meta = $meta;

$order->save();
```

<a name="date-casting"></a>
### Date Casting

By default, Eloquent will cast the `created_at` and `updated_at` columns to `DateTime` instances. You may customize this behavior by defining additional date casts in your model's `$casts` array:

```php
protected array $casts = [
    'registered_at' => 'datetime',
    'trial_ends_at' => 'date:Y-m-d',
];
```

#### Serialization Format

When casting dates with a specific format, that format will be used when the model is converted to an array or JSON:

```php
protected array $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
];
```

If no format is specified, the date will be serialized to `Y-m-d H:i:s` (ISO 8601-ish) by default.
```



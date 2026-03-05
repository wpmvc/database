# Schema Builder

- [Introduction](#introduction)
- [Creating Tables](#creating-tables)
- [Updating Tables](#updating-tables)
- [Dropping Columns](#dropping-columns)
- [Indexes & Constraints](#indexes-and-constraints)
- [Available Column Types](#available-column-types)

<a name="introduction"></a>
## Introduction

The `Schema` class provides a database agnostic way of manipulating tables. It works gracefully with all supported databases and has an expressive API for creating and modifying database tables.

<a name="creating-tables"></a>
## Creating Tables

To create a new database table, use the `create` method on the `Schema` class. The `create` method accepts two arguments: the first is the name of the table, while the second is a closure which receives a `Blueprint` object that may be used to define the new table:

```php
use WpMVC\Database\Schema;
use WpMVC\Database\Schema\Blueprint;

Schema::create('customers', function (Blueprint $table) {
    $table->big_increments('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});
```

When creating the table, you may use any of the schema builder's [column methods](#available-column-types) to define the table's columns.

#### Checking For Table Existence

You may easily check for the existence of a table or column using the `has_table` and `has_column` methods:

```php
if (Schema::has_table('customers')) {
    // The "customers" table exists...
}

if (Schema::has_column('customers', 'email')) {
    // The "customers" table exists and has an "email" column...
}
```

<a name="updating-tables"></a>
## Updating Tables

The `alter` method on the `Schema` class may be used to update existing tables. Like the `create` method, the `alter` method accepts the name of the table and a closure that receives a `Blueprint` instance:

```php
use WpMVC\Database\Schema;
use WpMVC\Database\Schema\Blueprint;

Schema::alter('customers', function (Blueprint $table) {
    $table->string('phone')->nullable();
});
```

#### SQL Preview

Most `Schema` methods (`create`, `alter`, `rename`, `drop`, `drop_if_exists`) accept an optional boolean as the final argument. If set to `true`, the method will return the generated SQL string instead of executing it:

```php
$sql = Schema::create('customers', function (Blueprint $table) {
    $table->big_increments('id');
}, true);
```

#### Renaming & Dropping Tables

To rename an existing database table, use the `rename` method:

```php
Schema::rename($from, $to);
```

To drop an existing table, you may use the `drop` or `drop_if_exists` methods:

```php
Schema::drop('customers');

Schema::drop_if_exists('customers');
```

<a name="indexes-and-constraints"></a>
## Indexes & Constraints

The schema builder supports several types of indexes. The following example creates a new `email` column and specifies that its values should be unique. 

```php
$table->string('email')->unique();
```

Alternatively, you may create the index after defining the column:

```php
$table->string('email');

$table->unique('email');
```

You may even pass an array of columns to an index method to create a compound index:

```php
$table->index(['account_id', 'created_at']);
```

#### Primary Keys

You may also define a primary key for the table:

```php
$table->primary('id');

// Or for composite keys:
$table->primary(['account_id', 'id']);
```

#### Foreign Key Constraints

WpMVC also provides support for creating foreign key constraints, which are used to force referential integrity at the database level. For example, let's define a `customer_id` column on the `products` table that references the `id` column on a `customers` table:

```php
use WpMVC\Database\Schema;
use WpMVC\Database\Schema\Blueprint;

Schema::create('products', function (Blueprint $table) {
    $table->big_increments('id');
    $table->unsigned_big_integer('customer_id');

    $table->foreign('customer_id')
          ->references('id')
          ->on('customers')
          ->on_delete('cascade');
});
```

> [!NOTE]
> Foreign keys are automatically checked to avoid duplicates. The naming convention used is `fk_{prefix}{table}_{column}`.

#### Dropping Indexes

To drop an index, you must specify the index's name. By default, WpMVC assigns a reasonable name to the indexes. You can simply pass the index name to the `drop_index` method:

```php
$table->drop_index('geo_state_index');
```

<a name="available-column-types"></a>
## Available Column Types

The schema builder blueprint offers a variety of methods that correspond to the different types of columns you can add to your database tables.

### Big Integer
#### big_increments()
The `big_increments` method creates an auto-incrementing `BIGINT UNSIGNED` (primary key) equivalent column:
```php
$table->big_increments('id');
```

#### unsigned_big_integer()
The `unsigned_big_integer` method creates an `UNSIGNED BIGINT` equivalent column:
```php
$table->unsigned_big_integer('customer_id');
```

#### big_integer()
The `big_integer` method creates a `BIGINT` equivalent column:
```php
$table->big_integer('votes');
```

### Strings & Text
#### string()
The `string` method creates a `VARCHAR` equivalent column of a given length:
```php
$table->string('name', 100);
```

#### text()
The `text` method creates a `TEXT` equivalent column:
```php
$table->text('description');
```

#### long_text()
The `long_text` method creates a `LONGTEXT` equivalent column. This column is nullable by default:
```php
$table->long_text('content');
```

### Numbers
#### integer()
The `integer` method creates an `INT` equivalent column:
```php
$table->integer('votes');
```

#### unsigned_integer()
The `unsigned_integer` method creates an `UNSIGNED INT` equivalent column:
```php
$table->unsigned_integer('votes');
```

#### tiny_integer()
The `tiny_integer` method creates a `TINYINT` equivalent column:
```php
$table->tiny_integer('status');
```

#### decimal()
The `decimal` method creates a `DECIMAL` equivalent column with the given precision (total digits) and scale (decimal digits):
```php
$table->decimal('amount', 8, 2);
```

#### float()
The `float` method creates a `FLOAT` equivalent column:
```php
$table->float('price');
```

#### boolean()
The `boolean` method creates a `BOOLEAN` equivalent column:
```php
$table->boolean('confirmed');
```

### Date & Time
#### timestamps()
The `timestamps` method adds `created_at` and `updated_at` `TIMESTAMP` equivalent columns:
```php
$table->timestamps();
```

#### timestamp()
The `timestamp` method creates a `TIMESTAMP` equivalent column. This column is nullable by default:
```php
$table->timestamp('added_on');
```

#### date()
The `date` method creates a `DATE` equivalent column:
```php
$table->date('birthday');
```

#### datetime()
The `datetime` method creates a `DATETIME` equivalent column:
```php
$table->datetime('published_at');
```

### Miscellaneous
#### enum()
The `enum` method creates an `ENUM` equivalent column with the given allowed values:
```php
$table->enum('difficulty', ['easy', 'hard']);
```

#### json()
The `json` method creates a `JSON` equivalent column:
```php
$table->json('options');
```

### Column Modifiers

In addition to the column types listed above, there are several column "modifiers" you may use while adding a column to a database table. For example, to make the column "nullable", you may use the `nullable` method:

```php
Schema::alter('users', function (Blueprint $table) {
    $table->string('email')->nullable();
});
```

#### nullable()
Allows the column to allow `NULL` values.

#### default()
Specify a "default" value for the column:
```php
$table->string('status')->default('draft');
```

#### comment()
Add a comment to the column:
```php
$table->string('role')->comment('User role in the system');
```

#### use_current()
Sets the default value for a timestamp column to the current timestamp:
```php
$table->timestamp('created_at')->use_current();
```

#### use_current_on_update()
Sets the column to automatically update to the current timestamp when the record is updated:
```php
$table->timestamp('updated_at')->use_current_on_update();
```

#### after()
When using MySQL, the `after` method may be used to specify the order of columns:
```php
$table->string('address')->after('email');
```

<a name="dropping-columns"></a>
## Dropping Columns

To drop columns from an existing table, you may use the `drop_column` method on the `Blueprint` instance within a `Schema::alter` callback:

```php
use WpMVC\Database\Schema;
use WpMVC\Database\Schema\Blueprint;

Schema::alter('customers', function (Blueprint $table) {
    $table->drop_column('phone');
    
    // You may also drop multiple columns
    $table->drop_column('address');
    $table->drop_column('social_security_number');
});
```

<p align="center">
  <a href="https://packagist.org/packages/wpmvc/database"><img src="https://img.shields.io/packagist/dt/wpmvc/database" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/wpmvc/database"><img src="https://img.shields.io/packagist/v/wpmvc/database" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/wpmvc/database"><img src="https://img.shields.io/packagist/l/wpmvc/database" alt="License"></a>
</p>

# WpMVC Database

**WpMVC Database** is a powerful SQL query builder tailored for WordPress plugins, offering a fluent and intuitive interface inspired by Laravel's Eloquent Query Builder. It simplifies database operations, relationships, and schema management for WordPress developers.

---

## Table of Contents

- [Installation](#installation)
- [Schema Builder](#schema-builder)
  - [Creating Tables](#creating-tables)
  - [Altering Tables](#altering-tables)
  - [Dropping or Renaming Tables](#dropping-or-renaming-tables)
  - [Returning SQL Queries](#returning-sql-queries)
  - [Foreign Key Safety](#foreign-key-safety)
  - [Supported Blueprint Methods](#supported-blueprint-methods)
- [Eloquent Models](#eloquent-models)
  - [Creating Models](#creating-models)
  - [Inserting Data](#inserting-data)
  - [Updating Data](#updating-data)
  - [Deleting Data](#deleting-data)
  - [Reading Data](#reading-data)
	- [Aggregates](#aggregates)
	- [Retrieving Models](#retrieving-models)
	- [Select Statements](#select-statements)
	- [Joins](#joins)
	- [Where Clauses](#where-clauses)
	- [Ordering, Grouping, Limit & Offset](#ordering-grouping-limit--offset)
- [Relationships](#relationships)
  - [One-to-One](#one-to-one)
  - [One-to-Many](#one-to-many)
  - [One-to-Many (Inverse) / Belongs To](#one-to-many-inverse--belongs-to)
  - [Constraining Query Loads](#constraining-query-loads)
- [License](#license)

---

## Installation

Install WpMVC Database using Composer:

```bash
composer require wpmvc/database
```

---

## Schema Builder

The Schema Builder provides a fluent interface for creating and modifying database tables.

### Creating Tables

Create a table with the `Schema::create` method:

```php
use WpMVC\Database\Schema;

Schema::create('products', function (Blueprint $table) {
	$table->big_increments('id');
	$table->unsigned_big_integer('category_id');
	$table->string('title');
	$table->long_text('description')->nullable();
	$table->enum('visibility', ['publish', 'draft'])->default('publish');
	$table->timestamps();

	$table->foreign('category_id')
		  ->references('id')
		  ->on('categories')
		  ->on_delete('cascade');
});
```

### Altering Tables

Modify an existing table with `Schema::alter`:

```php
Schema::alter('products', function (Blueprint $table) {
	$table->string('short_description')->after('title')->nullable();
	$table->drop_column('legacy_column');
	$table->drop_index('index_abc123');
});
```

### Dropping or Renaming Tables

Drop or rename tables as needed:

```php
Schema::drop_if_exists('products');
Schema::rename('old_products', 'products');
```

### Returning SQL Queries

Generate SQL without executing it by passing `true` as the third argument:

```php
$sql = Schema::create('products', function (Blueprint $table) {
	$table->string('title');
}, true);

echo $sql;
```

### Foreign Key Safety

Foreign keys are automatically checked to avoid duplicates. The naming convention is:

```
fk_{prefix}{table}_{column}
```

### Example Schema

A complete example for a `products` table:

```php
Schema::create('products', function (Blueprint $table) {
	$table->big_increments('id');
	$table->unsigned_big_integer('category_id');
	$table->string('title');
	$table->string('sku')->nullable();
	$table->long_text('description')->nullable();
	$table->decimal('price', 10, 2)->default(0.00);
	$table->boolean('is_active')->default(true);
	$table->enum('status', ['publish', 'draft'])->default('publish');
	$table->timestamps();

	$table->index(['status']);
	$table->foreign('category_id')
		  ->references('id')
		  ->on('categories')
		  ->on_delete('cascade');
});
```

### Supported Blueprint Methods

#### Column Types
- `big_increments(name)`: Auto-incrementing big integer (primary key).
- `unsigned_big_integer(name)`: Unsigned big integer.
- `integer(name)`: Signed integer.
- `unsigned_integer(name)`: Unsigned integer.
- `string(name, length)`: VARCHAR column with optional length.
- `text(name)`: TEXT column.
- `long_text(name)`: LONGTEXT column.
- `json(name)`: JSON column.
- `enum(name, values)`: ENUM column with specified values.
- `tiny_integer(name)`: TINYINT column.
- `timestamp(name)`: TIMESTAMP column.
- `timestamps()`: Adds `created_at` and `updated_at` TIMESTAMP columns.
- `boolean(name)`: BOOLEAN column.

#### Column Modifiers
- `nullable()`: Allows NULL values.
- `default(value)`: Sets a default value.
- `comment(text)`: Adds a column comment.
- `use_current()`: Sets the default to the current timestamp.
- `use_current_on_update()`: Updates timestamp on record update.
- `after(column)`: Places the column after another (only for `ALTER`).

#### Indexes & Constraints
- `primary(column|[columns])`: Sets primary key.
- `unique(column|[columns])`: Sets unique index.
- `index(column|[columns])`: Creates an index.
- `drop_column(name)`: Drops a column.
- `drop_index(name)`: Drops an index.
- `foreign(column)->references()->on()->on_delete()->on_update()`: Defines a foreign key constraint.

---

## Eloquent Models

### Creating Models

Define an Eloquent model by extending the `Model` class:

```php
namespace WpMVC\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Resolver;

class Post extends Model {
	public static function get_table_name(): string {
		return 'posts';
	}

	public function resolver(): Resolver {
		return new Resolver;
	}
}
```

### Inserting Data

Insert a single record:

```php
Post::query()->insert([
	'post_author' => wp_get_current_user()->ID,
	'post_title' => 'Test Post',
]);
```

Insert multiple records:

```php
Post::query()->insert([
	[
		'post_author' => wp_get_current_user()->ID,
		'post_title' => 'Test Post 1',
	],
	[
		'post_author' => wp_get_current_user()->ID,
		'post_title' => 'Test Post 2',
	],
]);
```

Insert and retrieve the ID:

```php
$post_id = Post::query()->insert_get_id([
	'post_author' => wp_get_current_user()->ID,
	'post_title' => 'Test Post',
]);
```

### Updating Data

Update a record based on a condition:

```php
Post::query()->where('post_id', 100)->update([
	'post_title' => 'Updated Post',
]);
```

### Deleting Data

Delete a record based on a condition:

```php
Post::query()->where('post_id', 100)->delete();
```

### Reading Data

#### Aggregates

Retrieve aggregate values like `count`, `max`, `min`, `avg`, or `sum`:

```php
$count = Post::query()->count();
```

#### Retrieving Models

Fetch all records:

```php
$posts = Post::query()->get();
```

Fetch a single record:

```php
$post = Post::query()->where('id', 100)->first();
```

#### Select Statements

Select specific columns:

```php
$posts = Post::query()->select('post_title', 'post_date')->get();
```

Use `distinct` for unique results:

```php
$posts = Post::query()->distinct()->select('post_title')->get();
```

#### Joins

Perform an inner join:

```php
$users = User::query()
	->join('contacts', 'users.id', '=', 'contacts.user_id')
	->select('users.*', 'contacts.phone', 'contacts.email')
	->get();
```

Perform left or right joins:

```php
$users = User::query()
	->left_join('posts', 'users.id', '=', 'posts.user_id')
	->get();
```

Advanced join with a closure:

```php
use WpMVC\Database\Query\JoinClause;

$posts = Post::query()->join('postmeta', function (JoinClause $join) {
	$join->on('postmeta.post_id', '=', 'posts.ID')
		 ->where('postmeta.meta_value', '>', 500);
})->get();
```

#### Where Clauses

Basic where clause:

```php
$posts = Post::query()->where('post_status', 'publish')->get();
```

Or where clause:

```php
$posts = Post::query()
	->where('post_status', 'publish')
	->orWhere('post_title', 'Test Post')
	->get();
```

Where exists clause:

```php
$posts = Post::query()->where_exists(function (Builder $query) {
	$query->select(1)
		  ->from('postmeta')
		  ->where_column('postmeta.post_id', 'posts.id')
		  ->limit(1);
})->get();
```

Where between:

```php
$posts = Post::query()->where_between('ID', [1, 100])->get();
```

Where in:

```php
$posts = Post::query()->where_in('ID', [100, 105])->get();
```

#### Ordering, Grouping, Limit & Offset

Order results:

```php
$posts = Post::query()->order_by('post_title', 'asc')->get();
```

Group results:

```php
$posts = Post::query()
	->group_by('post_author')
	->having('post_author', '>', 100)
	->get();
```

Limit and offset:

```php
$posts = Post::query()->offset(10)->limit(5)->get();
```

---

## Relationships

WpMVC Database supports common Eloquent relationships for managing related data.

### One-to-One

Define a one-to-one relationship (e.g., a `User` has one `Phone`):

```php
namespace WpMVC\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasOne;

class User extends Model {
	public function phone(): HasOne {
		return $this->has_one(Phone::class, 'ID', 'user_id');
	}
}
```

Retrieve users with their phones:

```php
$users = User::query()->with('phone')->get();
```

### One-to-Many

Define a one-to-many relationship (e.g., a `Post` has many `PostMeta`):

```php
namespace WpMVC\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\HasMany;

class Post extends Model {
	public function meta(): HasMany {
		return $this->has_many(PostMeta::class, 'ID', 'post_id');
	}
}
```

### One-to-Many (Inverse) / Belongs To

Define the inverse relationship (e.g., a `PostMeta` belongs to a `Post`):

```php
namespace WpMVC\App\Models;

use WpMVC\Database\Eloquent\Model;
use WpMVC\Database\Eloquent\Relations\BelongsToOne;

class PostMeta extends Model {
	public function post(): BelongsToOne {
		return $this->belongs_to_one(Post::class, 'post_id', 'ID');
	}
}
```

### Constraining Query Loads

Add conditions to relationship queries:

```php
use WpMVC\Database\Query\Builder;

$posts = Post::query()->with([
	'meta' => function (Builder $query) {
		$query->where('meta_id', 672);
	},
	'user',
])->get();
```

---

## License

WpMVC Database is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).
<p align="center">
  <a href="https://packagist.org/packages/wpmvc/database"><img src="https://img.shields.io/packagist/dt/wpmvc/database" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/wpmvc/database"><img src="https://img.shields.io/packagist/v/wpmvc/database" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/wpmvc/database"><img src="https://img.shields.io/packagist/l/wpmvc/database" alt="License"></a>
</p>

# WpMVC Database

**WpMVC Database** is a powerful SQL query builder tailored for WordPress plugins, offering a fluent and intuitive interface inspired by Laravel's Eloquent Query Builder. It simplifies database operations, relationships, and schema management for WordPress developers.

---

## Installation

Install WpMVC Database using Composer:

```bash
composer require wpmvc/database
```

---

## Modular Documentation

For detailed information on how to use WpMVC Database, please refer to the following guides:

1. [**Schema Builder**](docs/schema-builder.md) — Creating, altering, and dropping tables.
2. [**Eloquent Models**](docs/eloquent-models.md) — Creating models, configuration, mass assignment, and serialization.
3. [**Mutators & Casting**](docs/mutators-and-casting.md) — Accessors, mutators, and attribute casting.
4. [**Query Builder**](docs/query-builder.md) — Advanced retrieval, filtering, joins, unions, and transactions.
5. [**Eloquent Collections**](docs/collections.md) — Working with multi-result sets.
6. [**Relationships**](docs/relationships.md) — One-to-one, one-to-many, many-to-many, and polymorphic relationships.
7. [**Factories & Seeding**](docs/factories-and-seeding.md) — Generating dummy data for testing and seeding.

---

## Resolver Utility

Use the `Resolver` to manually resolve database table names with WordPress prefixes:

```php
use WpMVC\Database\Resolver;

$resolver = new Resolver();

// Get a single table name
$table = $resolver->table('my_custom_table'); // wp_my_custom_table

// Get multiple table names
[$posts, $users] = $resolver->table('posts', 'users');
```

---

## License

WpMVC Database is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).
# Database: Query Builder

- [Introduction](#introduction)
- [Retrieving Results](#retrieving-results)
    - [Aggregates](#aggregates)
    - [Pagination](#pagination)
- [Selects](#selects)
- [Joins](#joins)
- [Where Clauses](#where-clauses)
    - [Logical Grouping](#logical-grouping)
    - [Advanced Where Clauses](#advanced-where-clauses)
- [Ordering, Grouping, Limit, & Offset](#ordering-grouping-limit-and-offset)
- [Inserts](#inserts)
- [Updates](#updates)
- [Deletes](#deletes)
- [Transactions](#transactions)
- [Unions](#unions)
- [Debugging](#debugging)

<a name="introduction"></a>
## Introduction

WpMVC's database query builder provides a convenient, fluent interface to creating and running database queries. It can be used to perform most database operations in your application and works on all supported database systems.

The WpMVC query builder uses PDO parameter binding to protect your application against SQL injection attacks. There is no need to clean strings passed to the query builder as bindings.

> [!TIP]
> While you may use the `Builder::table()` method to interact with database tables directly, it is generally recommended to use [Eloquent Models](eloquent-models.md) for a more expressive and powerful development experience.

<a name="retrieving-results"></a>
## Retrieving Results

#### Retrieving All Rows From A Table

To start a query, you may use the `query` method on an Eloquent model. This method returns a fluent query builder instance for the model's table, allowing you to chain more constraints onto the query and then finally retrieve the results using the `get` method:

```php
use MyPluginNamespace\App\Models\User;

$users = User::query()->get();

foreach ($users as $user) {
    echo $user->display_name;
}
```

The `get` method returns an `WpMVC\Database\Eloquent\Collection` instance containing the results of the query. You may access each column's value by accessing the column as a property of the model:

```php
foreach ($users as $user) {
    echo $user->display_name;
}
```

#### Retrieving A Single Row / Column From A Table

If you just need to retrieve a single row from a database table, you may use the `first` method. This method will return a single model instance:

```php
$user = User::query()->where('display_name', 'John')->first();

echo $user->display_name;
```

#### Retrieving A Single Row By ID

If you have a row's `id` column value, you may retrieve the row using the `find` method:

```php
$user = User::query()->find(3);
```

<a name="aggregates"></a>
### Aggregates

The query builder also provides a variety of methods for retrieving aggregate values like `count`, `max`, `min`, `avg`, and `sum`. You may call any of these methods after constructing your query:

```php
$count = User::query()->count();

$max_comments = Post::query()->max('comment_count');
```

Of course, you may combine these methods with other clauses:

```php
$average = Post::query()
                ->where('post_status', 'publish')
                ->avg('comment_count');
```

#### Determining If Records Exist

Instead of using the `count` method to determine if any records exist that match your query's constraints, you may use the `exists` and `doesnt_exist` methods:

```php
if (Post::query()->where('post_status', 'publish')->exists()) {
    // ...
}

if (Post::query()->where('post_status', 'publish')->doesnt_exist()) {
    // ...
}
```

<a name="pagination"></a>
### Pagination

WpMVC provides a powerful `pagination` method to handle result segmenting. The `pagination` method takes the current page and number of results per page:

```php
$users = User::query()->pagination(1, 15);
```

The `pagination` method returns a `WpMVC\Database\Pagination\LengthAwarePaginator` instance, which provides methods for the total count, total pages, and is convertible to an array or JSON.

```php
$users = User::query()->pagination(1, 15);

$total = $users->total(); // Total count of matching records
$current_page = $users->current_page();
$last_page = $users->last_page();
```

> [!IMPORTANT]
> Ensure you use the method name `pagination()`. The standard Laravel name `paginate()` is not available in the current version of WpMVC.

<a name="selects"></a>
## Selects

Of course, you may not always want to select all columns from a database table. Using the `select` method, you can specify a custom `select` clause for the query:

```php
$users = User::query()->select('display_name', 'user_email')->get();
```

The `distinct` method allows you to force the query to return distinct results:

```php
$users = User::query()->distinct()->get();
```

If you already have a query builder instance and you wish to add a column to its existing select clause, you may use the `add_select` method:

```php
$query = User::query()->select('display_name');

$users = $query->add_select('age')->get();
```

<a name="joins"></a>
## Joins

#### Inner Join Clause

The query builder may also be used to add join clauses to your queries. To perform a basic "inner join", you may use the `join` method on a query builder instance. The first argument passed to the `join` method is the name of the table you need to join to, while the remaining arguments specify the column constraints for the join:

```php
$posts = Post::query()
            ->join('postmeta', 'posts.ID', '=', 'postmeta.post_id')
            ->select('posts.*', 'postmeta.meta_key', 'postmeta.meta_value')
            ->get();
```

#### Left Join / Right Join Clause

If you would like to perform a "left join" or "right join" instead of an "inner join", use the `left_join` or `right_join` methods:

```php
$users = User::query()
            ->left_join('posts', 'users.ID', '=', 'posts.post_author')
            ->get();
```

<a name="where-clauses"></a>
## Where Clauses

You may use the `where` method on a query builder instance to add `where` clauses to the query. The most basic call to the `where` method requires three arguments. The first argument is the name of the column. The second argument is an operator, which can be any of the database's supported operators. Finally, the third argument is the value to evaluate against the column.

For example, here is a query that verifies the value of the "user_status" column is equal to 0:

```php
$users = User::query()->where('user_status', '=', 0)->get();
```

For convenience, if you want to verify that a column is equal to a given value, you may pass the value as the second argument to the `where` method:

```php
$users = User::query()->where('user_status', 0)->get();
```

As noted earlier, you may use any operator that is supported by your database system:

```php
$users = User::query()
                ->where('user_status', '>=', 0)
                ->get();

$users = User::query()
                ->where('user_status', '<>', 1)
                ->get();

$users = User::query()
                ->where('display_name', 'like', 'T%')
                ->get();
```

#### where_key

The `where_key` method may be used to filter a query by the model's primary key:

```php
$user = User::query()->where_key(1)->first();
```

#### where_raw / or_where_raw

The `where_raw` and `or_where_raw` methods allow you to inject raw SQL into your query. These methods accept a raw SQL string as their first argument and an optional array of bindings as their second argument:

```php
$users = User::query()
                ->where_raw('user_status > ?', [0])
                ->get();

$users = User::query()
                ->or_where_raw('user_status > ?', [1])
                ->get();
```

#### Or Where Clauses

You may chain where constraints together as well as add `or` clauses to the query. The `or_where` method accepts the same arguments as the `where` method:

```php
$users = User::query()
                    ->where('user_status', '>', 0)
                    ->or_where('display_name', 'John')
                    ->get();
```

<a name="logical-grouping"></a>
### Logical Grouping

Sometimes you may need to group several "where" clauses within parentheses in order to achieve your query's desired logical grouping. To achieve this, you may pass a closure to the `where` method:

```php
$users = User::query()
            ->where('display_name', '=', 'John')
            ->where(function ($query) {
                $query->where('user_status', '>', 0)
                      ->or_where('user_nicename', '=', 'admin');
            })
            ->get();
```

As you can see, passing a closure to the `where` method instructs the query builder to begin a constraint group. The closure will receive a query builder instance which you can use to set the constraints that should be contained within the parenthesis group. The example above will produce the following SQL:

```sql
select * from users where display_name = 'John' and (user_status > 0 or user_nicename = 'admin')
```

<a name="advanced-where-clauses"></a>
### Advanced Where Clauses

#### where_between / or_where_between

The `where_between` method verifies that a column's value is between two values:

```php
$users = User::query()
           ->where_between('user_status', [0, 1])
           ->get();
```

#### where_in / where_not_in / or_where_in / or_where_not_in

The `where_in` method verifies that a given column's value is contained within the given array:

```php
$users = User::query()
                    ->where_in('ID', [1, 2, 3])
                    ->get();
```

#### where_null / where_not_null / or_where_null / or_where_not_null

The `where_null` method verifies that the value of the given column is `NULL`:

```php
$users = User::query()
                ->where_null('updated_at')
                ->get();
```


#### where_column / or_where_column

The `where_column` method may be used to verify that two columns are equal:

```php
$users = User::query()
                ->where_column('user_login', 'user_nicename')
                ->get();
```

You may also pass a comparison operator to the method:

```php
$users = User::query()
                ->where_column('updated_at', '>', 'created_at')
                ->get();
```

#### where_exists / where_not_exists / or_where_exists / or_where_not_exists

The `where_exists` method allows you to write `WHERE EXISTS` SQL clauses. The `where_exists` method accepts a `Closure` argument, which will receive a query builder instance allowing you to define the query that should be placed inside of the "exists" clause:

```php
$users = User::query()
            ->where_exists(function ($query) {
                $query->select('ID')
                      ->from('posts')
                      ->where_column('posts.post_author', 'users.ID');
            })
            ->get();
```

The query above will produce the following SQL:

```sql
select * from users
where exists (
    select ID from posts where posts.post_author = users.ID
)
```

<a name="conditional-clauses"></a>
### Conditional Clauses

Sometimes you may want clauses to apply to a query only when something else is true. For instance, you may only want to apply a `where` statement if a given input value is present on the incoming HTTP request. You may accomplish this using the `when` method:

```php
$role = 'admin';

$users = User::query()
            ->when($role, function ($query, $role) {
                return $query->where('user_role', $role);
            })
            ->get();
```

The `when` method only executes the given Closure when the first parameter is `true`. If the first parameter is `false`, the Closure will not be executed.

You may pass another Closure as the third parameter to the `when` method. This Closure will execute if the first parameter evaluates as `false`:

```php
$role = null;

$users = User::query()
            ->when($role, function ($query, $role) {
                return $query->where('user_role', $role);
            }, function ($query) {
                return $query->where('user_role', 'subscriber');
            })
            ->get();
```

The `unless` method is the inverse of `when`. The given Closure will only be executed if the first parameter is `false`:

```php
$verified = false;

$users = User::query()
            ->unless($verified, function ($query) {
                return $query->where('user_status', 0);
            })
            ->get();
```

#### tap

The `tap` method allows you to execute a callback with the query builder instance, which is useful for performing side effects or conditionally applying builder logic without breaking the method chain:

```php
$users = User::query()
            ->where('user_status', 1)
            ->tap(function ($query) {
                // Perform some logging or side effect
            })
            ->get();
```

<a name="ordering-grouping-limit-and-offset"></a>
## Ordering, Grouping, Limit, & Offset

#### Ordering

The `order_by` method allows you to sort the result of the query by a given column. The first argument accepted by the `order_by` method should be the column you wish to sort by, while the second argument determines the direction of the sort and may be either `asc` or `desc`:

```php
$users = User::query()
                ->order_by('display_name', 'desc')
                ->get();
```

#### latest / oldest

The `latest` and `oldest` methods allow you to easily order results by date. By default, the result will be ordered by the table's `created_at` column:

```php
$user = User::query()->latest()->first();
```

#### Grouping

The `group_by` and `having` methods may be used to group the query results. The `having` method's signature is similar to that of the `where` method:

```php
$posts = Post::query()
                ->group_by('post_author')
                ->having('comment_count', '>', 100)
                ->get();
```

#### Limit & Offset

The `offset` and `limit` methods may be used to limit the number of results returned by the query or to skip a given number of results in the query:

```php
$users = User::query()->offset(10)->limit(5)->get();
```

#### Chunking Results

If you need to work with thousands of database records, consider using the `chunk` method. This method retrieves a small chunk of results at a time and feeds each chunk into a `Closure` for processing:

```php
User::query()->chunk(200, function ($users) {
    foreach ($users as $user) {
        // Process user...
    }
});
```

You may stop further chunks from being processed by returning `false` from the `Closure`:

```php
User::query()->chunk(200, function ($users) {
    // Process the records...

    return false;
});
```

#### Streaming Results Lazily

The `cursor` method allows you to iterate through your database records using a cursor, which will only execute a single query. When processing large amounts of data, the `cursor` method may be used to greatly reduce your memory usage:

```php
foreach (User::query()->where('user_status', 1)->cursor() as $user) {
    // Process user...
}
```

The `cursor` returns a PHP `Generator` instance.

<a name="inserts"></a>
## Inserts

#### Inserts

The query builder also provides an `insert` method for inserting records into the database table. The `insert` method accepts an array of column names and values:

```php
User::query()->insert([
    'user_login' => 'john_doe',
    'user_email' => 'john@example.com'
]);
```

#### Auto-Incrementing IDs

If the table has an auto-incrementing id, use the `insert_get_id` method to insert a record and then retrieve the ID:

```php
$id = User::query()->insert_get_id([
    'user_login' => 'john_doe',
    'user_email' => 'john@example.com'
]);
```

You may even insert several records at once by passing an array of arrays. Each array represents a row that should be inserted into the table:

```php
User::query()->insert([
    ['user_email' => 'taylor@example.com', 'display_name' => 'Taylor'],
    ['user_email' => 'dayle@example.com', 'display_name' => 'Dayle'],
]);
```

<a name="updates"></a>
## Updates

In addition to inserting records into the database, the query builder can also update existing records using the `update` method. The `update` method, like the `insert` method, accepts an array of column and value pairs indicating the columns to be updated. You may constrain the `update` query using `where` clauses:

```php
$affected = User::query()
              ->where('ID', 1)
              ->update(['user_status' => 1]);
```


<a name="deletes"></a>
## Deletes

The query builder's `delete` method may be used to delete records from the table. You may constrain `delete` statements by adding `where` clauses before calling the `delete` method:

```php
User::query()->delete();

User::query()->where('user_status', '>', 0)->delete();
```


<a name="transactions"></a>
## Transactions

The query builder also includes a static `transaction` method to help you run a set of operations within a database transaction:

```php
use WpMVC\Database\Query\Builder;

Builder::transaction(function () {
    User::query()->where('ID', 1)->update(['user_status' => 1]);
    Post::query()->where('post_author', 1)->delete();
});
```

If an exception is thrown within the transaction closure, the transaction will automatically be rolled back. If the closure executes successfully, the transaction will automatically be committed.

#### Unions

The query builder also provides a convenient way to "union" two queries together. For example, you may create an initial query and then use the `union` method to union it with a second query:

```php
$first = User::query()->where('user_login', 'admin');

$users = User::query()
            ->where('user_login', 'editor')
            ->union($first)
            ->get();
```

The `union_all` method is also available and has the same method signature as the `union` method.

<a name="debugging"></a>
## Debugging

You may use the `to_sql` and `get_raw_sql` methods while building a query to see the SQL output.

The `get_raw_sql` method returns the SQL with placeholders:

```php
$sql = User::query()->where('ID', 1)->get_raw_sql();
// select * from wp_users where ID = ?
```

The `to_sql` method returns the SQL with the values already bound and escaped:

```php
$sql = User::query()->where('ID', 1)->to_sql();
// select * from wp_users where ID = 1
```

# Eloquent: Collections

- [Introduction](#introduction)
- [Available Methods](#available-methods)

<a name="introduction"></a>
## Introduction

All Eloquent methods that return more than one model result will return instances of the `WpMVC\Database\Eloquent\Collection` class, including results retrieved via the `get` method or accessed via a [relationship](relationships.md). 

All Eloquent collections extend the base WpMVC collection object; therefore, they naturally inherit dozens of methods used to fluently work with the underlying array of Eloquent models. Be sure to review the WpMVC collection documentation to learn all about these helpful methods!

All collections also serve as iterators, allowing you to loop over them as if they were simple PHP arrays:

```php
use MyPluginNamespace\App\Models\User;

$users = User::query()->where('active', 1)->get();

foreach ($users as $user) {
    echo $user->display_name;
}
```

Since collections are much more powerful than arrays, they expose an intuitive, fluent interface of method chaining. For example, you may remove all inactive models and gather the first name for each remaining user:

```php
$names = $users->filter(function ($user) {
        return $user->user_status === 0;
    })
    ->map(function ($user) {
        return $user->display_name;
    });
```

<a name="available-methods"></a>
## Available Methods

The Eloquent collection class provides an enormous variety of methods for iterating and manipulating the items in the collection. For brevity, we'll cover a few of the most commonly used methods below.

#### all()
The `all` method returns the underlying array represented by the collection:
```php
$collection->all();
```

#### chunk()
The `chunk` method breaks the collection into multiple, smaller collections of a given size:
```php
$chunks = $collection->chunk(4);
```

#### contains()
The `contains` method determines whether the collection contains a given item:
```php
if ($collection->contains('Apple')) {
    // ...
}
```

#### count()
The `count` method returns the total number of items in the collection:
```php
$count = $collection->count();
```

#### each()
The `each` method iterates over the items in the collection and passes each item to a closure:
```php
$collection->each(function ($item, $key) {
    // ...
});
```

#### filter()
The `filter` method filters the collection using a given closure, keeping only those items that pass a given truth test:
```php
$filtered = $collection->filter(function ($item, $key) {
    return $item > 2;
});
```

#### first()
The `first` method returns the first element in the collection that passes a given truth test:
```php
$first = $collection->first(function ($item, $key) {
    return $item > 2;
});
```

#### map()
The `map` method iterates through the collection and passes each value to the given closure. The closure is free to modify the item and return it, thus forming a new collection of modified items:
```php
$multiplied = $collection->map(function ($item, $key) {
    return $item * 2;
});
```

#### pluck()
The `pluck` method retrieves all of the values for a given key:
```php
$plucked = $collection->pluck('display_name');
```

#### push()
The `push` method appends an item to the end of the collection:
```php
$collection->push('Tea');
```

#### sort_by()
The `sort_by` method sorts the collection by the given key:
```php
$sorted = $collection->sort_by('comment_count');
```

#### unique()
The `unique` method returns all of the unique items in the collection:
```php
$unique = $collection->unique();
```
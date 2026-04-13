---
description: Sort API resources by attributes or relationships with configurable direction constraints.
---

# Sorts

Sorts let API consumers control the order of results by sending a `sort` query parameter. A plain attribute name sorts ascending; a `-` prefix sorts descending. You declare which attributes are sortable and optionally restrict the allowed direction.

## URL format

```
GET /posts?sort=created_at          # ascending
GET /posts?sort=-created_at         # descending
GET /posts?sort=title,-created_at   # multiple sorts
```

## Direction constants

`AllowedSort` exposes three direction constants:

| Constant | Behaviour |
|---|---|
| `AllowedSort::BOTH` | Accepts both ascending and descending (default) |
| `AllowedSort::ASCENDANT` | Only `asc` direction is accepted |
| `AllowedSort::DESCENDANT` | Only `desc` direction is accepted |

The `SortDirection` enum mirrors these as string values:

```php
use OpenSoutheners\LaravelApiable\Http\SortDirection;

SortDirection::BOTH       // '*'
SortDirection::ASCENDANT  // 'asc'
SortDirection::DESCENDANT // 'desc'
```

The global default direction is configured in `config/apiable.php`:

```php
'requests' => [
    'sorts' => [
        'default_direction' => AllowedSort::BOTH,
    ],
],
```

## Static constructors

```php
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

AllowedSort::make('created_at')          // uses default_direction from config
AllowedSort::ascendant('created_at')     // only asc
AllowedSort::descendant('created_at')    // only desc
```

## Allowing sorts

{% tabs %}
{% tab title="Using methods" %}
Pass `AllowedSort` instances to `allowing()`, or call `allowSort()` directly:

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedSort::make('created_at'),
            AllowedSort::ascendant('title'),
            AllowedSort::descendant('review_points'),
        ]);
}
```

Using `allowSort()` directly:

```php
return JsonApiResponse::from(Post::class)
    ->allowSort('created_at')                          // both directions
    ->allowSort('title', AllowedSort::ASCENDANT)       // ascendant only
    ->allowSort(AllowedSort::descendant('price'));
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[SortQueryParam('created_at')]
#[SortQueryParam('title', AllowedSort::ASCENDANT)]
#[SortQueryParam('review_points', AllowedSort::DESCENDANT, description: 'Sort by review score')]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`SortQueryParam` accepts: `attribute`, `direction` (constant, default `AllowedSort::BOTH`), and `description`.
{% endtab %}
{% endtabs %}

## Sorting by relationship attributes

When the sort attribute contains a dot (`relationship.column`), the package automatically adds a JOIN so the outer query can order by the related table's column:

```php
AllowedSort::make('author.name')
AllowedSort::ascendant('tags.label')
```

For `BelongsTo` and `HasOne` relationships, a standard LEFT JOIN is added. For `BelongsToMany`, a correlated subquery is used instead. The join is only added once even if called multiple times.

```
GET /posts?sort=author.name
GET /posts?sort=-author.name
```

{% hint style="warning" %}
Self-referential relationships (where the related table equals the main table) are aliased automatically to avoid ambiguity. No manual configuration is needed.
{% endhint %}

## Default sorts

Default sorts apply when no `sort` parameter is present in the request. They do not conflict with user-supplied sorts — if the user sends a sort, default sorts are skipped entirely.

{% tabs %}
{% tab title="Using methods" %}
```php
use OpenSoutheners\LaravelApiable\Http\DefaultSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

// Shorthand — defaults to ascending
JsonApiResponse::from(Post::class)
    ->applyDefaultSort('created_at');

// Explicit direction using DefaultSort constants
JsonApiResponse::from(Post::class)
    ->applyDefaultSort('created_at', DefaultSort::DESCENDANT);

// Using the DefaultSort class
JsonApiResponse::from(Post::class)
    ->applyDefaultSort(DefaultSort::descendant('created_at'));
```

`DefaultSort` has the same static constructors as `AllowedSort` (`make()`, `ascendant()`, `descendant()`) but its direction constants are `DefaultSort::ASCENDANT` and `DefaultSort::DESCENDANT` (no `BOTH`, since a default must commit to a direction).
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultSort;
use OpenSoutheners\LaravelApiable\Http\DefaultSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[ApplyDefaultSort('created_at', DefaultSort::DESCENDANT)]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`ApplyDefaultSort` accepts: `attribute` and `direction` (optional, defaults to ascending).
{% endtab %}
{% endtabs %}

---
description: Filter API resources by attributes, relationships, or query scopes with configurable operators.
---

# Filters

Filters let API consumers narrow results by sending `filter[attribute]=value` query parameters. You control which attributes are filterable, which operators are allowed, and optionally which values are accepted.

## URL format

```
GET /posts?filter[title]=laravel
GET /posts?filter[author.name]=Taylor
GET /posts?filter[review_points][gt]=4
GET /posts?filter[status]=published,draft
```

Multiple values separated by commas are treated as `OR` conditions. Multiple `filter[]` parameters for the same attribute are accumulated.

## Operators

| Constant | String key | SQL behaviour |
|---|---|---|
| `AllowedFilter::SIMILAR` | `like` | `LIKE '%value%'` |
| `AllowedFilter::EXACT` | `equal` | `= 'value'` |
| `AllowedFilter::SCOPE` | `scope` | calls Eloquent named scope |
| `AllowedFilter::LOWER_THAN` | `lt` | `< value` |
| `AllowedFilter::LOWER_OR_EQUAL_THAN` | `lte` | `<= value` |
| `AllowedFilter::GREATER_THAN` | `gt` | `> value` |
| `AllowedFilter::GREATER_OR_EQUAL_THAN` | `gte` | `>= value` |

The default operator is `SIMILAR` (`LIKE`). You can change the global default in `config/apiable.php`:

```php
'requests' => [
    'filters' => [
        'default_operator' => AllowedFilter::EXACT,
    ],
],
```

## Static constructors

Each operator has a dedicated static constructor on `AllowedFilter`:

```php
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;

AllowedFilter::make('title')            // uses default operator from config
AllowedFilter::similar('title')         // LIKE '%value%'
AllowedFilter::exact('title')           // = 'value'
AllowedFilter::greaterThan('price')     // > value
AllowedFilter::greaterOrEqualThan('price')  // >= value
AllowedFilter::lowerThan('price')       // < value
AllowedFilter::lowerOrEqualThan('price')    // <= value
AllowedFilter::scoped('published')      // calls scopePublished($query, $value)
```

## Allowing filters

{% tabs %}
{% tab title="Using methods" %}
Pass `AllowedFilter` instances to `allowing()`, or call `allowFilter()` directly:

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedFilter::similar('title'),
            AllowedFilter::exact('status'),
            AllowedFilter::greaterThan('review_points'),
        ]);
}
```

Using `allowFilter()` directly:

```php
return JsonApiResponse::from(Post::class)
    ->allowFilter('title')                          // default operator
    ->allowFilter('status', AllowedFilter::EXACT)   // explicit operator
    ->allowFilter(AllowedFilter::lowerThan('price'));
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[FilterQueryParam('title', AllowedFilter::SIMILAR)]
#[FilterQueryParam('status', AllowedFilter::EXACT)]
#[FilterQueryParam('review_points', AllowedFilter::GREATER_THAN)]
#[FilterQueryParam('review_points', AllowedFilter::LOWER_OR_EQUAL_THAN)]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

The `FilterQueryParam` attribute accepts: `attribute`, `type` (operator constant), `values` (restriction), and an optional `description` for documentation generation.
{% endtab %}
{% endtabs %}

## Filtering by relationship attributes

Use dot notation to filter by an attribute on a related model. The package automatically wraps the query in a `has()` constraint:

```php
AllowedFilter::exact('author.name')
AllowedFilter::similar('tags.label')
```

API consumers then send:

```
GET /posts?filter[author.name]=Taylor
```

## Restricting allowed values

Pass an array (or string) as the second argument to restrict which values are accepted. Requests with values outside this list are rejected:

```php
AllowedFilter::exact('status', ['published', 'draft'])
AllowedFilter::similar('title', ['laravel', 'php'])
```

Using the PHP attribute:

```php
#[FilterQueryParam('status', AllowedFilter::EXACT, ['published', 'draft'])]
```

## Scoped filters

Scoped filters call an Eloquent [named scope](https://laravel.com/docs/eloquent#local-scopes) on your model. The consumer sends a truthy value (typically `1`) to activate the scope:

```php
// Model
public function scopePublished(Builder $query): void
{
    $query->where('published_at', '<=', now());
}

// Controller
AllowedFilter::scoped('published')
```

Request: `GET /posts?filter[published]=1`

The package resolves `published` → `scopePublished` via `Str::camel()`.

### Scoped filters with named arguments

Scopes that accept arguments can receive them via keyed sub-parameters:

```
GET /posts?filter[between][min]=10&filter[between][max]=50
```

```php
public function scopeBetween(Builder $query, int $min, int $max): void
{
    $query->whereBetween('price', [$min, $max]);
}

AllowedFilter::scoped('between')
```

### Enforcing `_scoped` suffix

When `requests.filters.enforce_scoped_names` is `true` in config, scope filter names must carry a `_scoped` suffix in the URL (`filter[published_scoped]=1`). This avoids ambiguity with attribute names:

```php
'requests' => [
    'filters' => [
        'enforce_scoped_names' => true,
    ],
],
```

With enforcement on, use `AllowedFilter::scoped()` — it automatically appends the suffix to the filter name.

## Default filters

Default filters are applied automatically when the consumer sends no filter parameters. They do not require the user to send anything.

{% tabs %}
{% tab title="Using methods" %}
```php
use OpenSoutheners\LaravelApiable\Http\DefaultFilter;

// Using the convenience method (attribute = value, operator defaults to SIMILAR)
JsonApiResponse::from(Post::class)
    ->applyDefaultFilter('status', AllowedFilter::EXACT, 'published');

// Using the DefaultFilter class
JsonApiResponse::from(Post::class)
    ->applyDefaultFilter(DefaultFilter::exact('status', 'published'));
```

`applyDefaultFilter()` accepts the same signature as `allowFilter()`: an attribute string (with optional operator and values), or a `DefaultFilter` instance.
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[ApplyDefaultFilter('status', AllowedFilter::EXACT, 'published')]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`ApplyDefaultFilter` takes: `attribute`, `operator` (optional constant), and `values`.
{% endtab %}
{% endtabs %}

{% hint style="info" %}
Default filters only activate when no user-supplied allowed filters are present in the request. If the user sends any allowed `filter[]` parameter, default filters are skipped entirely.
{% endhint %}

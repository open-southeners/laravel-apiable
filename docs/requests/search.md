---
description: Perform full-text search on your resources using Laravel Scout integration.
---

# Search

Full-text search lets API consumers search across your resources using a single query term. The package integrates with [Laravel Scout](https://laravel.com/docs/scout) to delegate search to your configured search driver (Meilisearch, Algolia, Typesense, or the database driver).

{% hint style="warning" %}
This feature requires Laravel Scout to be installed and your model to use the `Laravel\Scout\Searchable` trait. Without it, the `?q=` parameter is silently ignored.
{% endhint %}

## URL format

Consumers can use either the `q` or `search` parameter name:

```
GET /posts?q=laravel
GET /posts?search=laravel
```

Both are equivalent. `q` takes precedence if both are sent.

## How it works

When a search query is present and search is allowed, the package:

1. Calls `Model::search($term)` via Scout to obtain matching model IDs
2. Restricts the main Eloquent query with `whereKey($ids)` so all other pipeline stages (filters, includes, sorts, pagination) still apply normally

The response format and pagination are unchanged — only the set of matching records changes.

## Enabling search

{% tabs %}
{% tab title="Using methods" %}
```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowSearch();
}
```

`allowSearch()` accepts an optional boolean argument (default `true`) if you need to conditionally enable it:

```php
->allowSearch($request->user()->can('search-posts'))
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[SearchQueryParam]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`SearchQueryParam` accepts: `allowSearch` (bool, default `true`) and `description`.
{% endtab %}
{% endtabs %}

## Search filters

Search filters narrow results within the Scout search itself — they are passed to the Scout builder (not to Eloquent) before IDs are extracted. This is useful when your search driver supports attribute filtering.

### `AllowedSearchFilter::make()`

```php
use OpenSoutheners\LaravelApiable\Http\AllowedSearchFilter;

AllowedSearchFilter::make('status')
AllowedSearchFilter::make('status', ['published', 'draft'])  // restrict values
```

The second argument restricts which values the consumer can pass. `'*'` (default) accepts any value.

### URL format for search filters

Search filters are sent as sub-parameters of `q` or `search`:

```
GET /posts?q=laravel&q[filter][status]=published
```

### Allowing search filters

{% tabs %}
{% tab title="Using methods" %}
```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedSearchFilter;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowSearch()
        ->allowSearchFilter('status')
        ->allowSearchFilter(AllowedSearchFilter::make('category', ['php', 'laravel']));
}
```

`allowSearchFilter()` accepts either an `AllowedSearchFilter` instance or a string attribute name with an optional values argument:

```php
->allowSearchFilter('status', ['published', 'draft'])
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[SearchQueryParam]
#[SearchFilterQueryParam('status', ['published', 'draft'])]
#[SearchFilterQueryParam('category')]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`SearchFilterQueryParam` accepts: `attribute`, `values` (default `'*'`), and `description`.

{% hint style="info" %}
Unlike `SearchQueryParam`, `SearchFilterQueryParam` is repeatable — you can add multiple search filter attributes.
{% endhint %}
{% endtab %}
{% endtabs %}

## Multiple search filter values

When a consumer sends multiple values for the same search filter, the package calls `whereIn()` on the Scout builder:

```
GET /posts?q=laravel&q[filter][status][]=published&q[filter][status][]=draft
```

A single value uses `where()`. Multiple values use `whereIn()`.

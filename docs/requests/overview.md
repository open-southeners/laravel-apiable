---
description: Allow API consumers to filter, sort, include relationships, select fields, append accessors, and search your resources.
---

# Request Features Overview

Laravel Apiable lets you expose a rich, controlled set of query capabilities to API consumers. Rather than building custom query logic per endpoint, you declare what is allowed and the package handles the rest — parsing, validating, and applying each parameter to the underlying Eloquent query.

## Available features

| Feature | Query parameter | Documentation |
|---|---|---|
| Filters | `filter[attribute]=value` | [Filters](filters.md) |
| Sorts | `sort=attribute` or `sort=-attribute` | [Sorts](sorts.md) |
| Includes | `include=relationship` | [Includes](includes.md) |
| Sparse fieldsets | `fields[type]=col1,col2` | [Fields](fields.md) |
| Appends | `appends[type]=accessor` | [Appends](appends.md) |
| Full-text search | `?q=term` or `?search=term` | [Search](search.md) |
| Param validation | — | [Validation](validation.md) |

## Two approaches

All request features can be configured in two ways. Both produce identical behaviour — choose the style that fits your project.

### Using fluent methods on `JsonApiResponse`

Call the `allowing()` method with a mixed array of `Allowed*` instances, or use the dedicated per-feature methods (`allowFilter()`, `allowSort()`, etc.):

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedFilter::similar('title'),
            AllowedFilter::exact('author.name'),
            AllowedSort::make('created_at'),
            AllowedInclude::make('author'),
        ]);
}
```

You can mix any combination of `AllowedFilter`, `AllowedSort`, `AllowedInclude`, `AllowedFields`, `AllowedAppends`, and `AllowedSearchFilter` in the same `allowing()` call.

### Using PHP Attributes on controller methods or classes

Attributes are declared above your controller method (or above the class for shared configuration). They are resolved automatically when `JsonApiResponse` processes the request:

```php
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[FilterQueryParam('title', AllowedFilter::SIMILAR)]
#[FilterQueryParam('author.name', AllowedFilter::EXACT)]
#[SortQueryParam('created_at')]
#[IncludeQueryParam('author')]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

{% hint style="info" %}
Every `*QueryParam` attribute accepts an optional `$description` string as its last parameter. This description is used by the `apiable:docs` command when generating API documentation. See [Generating Documentation](../documentation/) for details.
{% endhint %}

Attributes can be placed at the **class level** (applying to all methods) or at the **method level** (applying to that action only). Method-level attributes take precedence.

## Combining `allowing()` with individual methods

The `allowing()` method is a convenience wrapper. Underneath it calls the same individual methods, so you can mix both styles freely:

```php
return JsonApiResponse::from(Post::class)
    ->allowing([
        AllowedFilter::similar('title'),
        AllowedSort::make('created_at'),
    ])
    ->allowInclude('author')
    ->allowInclude('tags')
    ->allowSearch();
```

## Next steps

- [Filters](filters.md) — filter by attribute, relationship, or query scope
- [Sorts](sorts.md) — sort results ascending or descending
- [Includes](includes.md) — eager-load relationships as compound documents
- [Fields](fields.md) — select specific columns per resource type
- [Appends](appends.md) — include computed model accessors
- [Search](search.md) — full-text search via Laravel Scout
- [Validation](validation.md) — reject unrecognised or invalid query parameters

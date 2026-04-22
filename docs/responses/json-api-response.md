---
description: The main entry point for building JSON:API responses with filtering, sorting, pagination and more.
---

# JsonApiResponse

`JsonApiResponse` is the primary class for building full-featured JSON:API responses. It wires together the request query pipeline (filters, sorts, includes, fields, search), pagination, viewable scoping, and serialization — all from a single fluent interface.

It implements Laravel's `Responsable` interface, so you can return it directly from a controller.

## Creating a response

### JsonApiResponse::from()

The static factory `from()` accepts a model class string or an Eloquent builder instance:

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

// From a model class — starts a fresh query
JsonApiResponse::from(Film::class);

// From a builder — uses the given query as the base
JsonApiResponse::from(Film::where('active', true));
```

### using()

If you already have a `JsonApiResponse` instance (e.g. resolved from the container), you can set or replace the query with `using()`:

```php
$response = app(JsonApiResponse::class)->using(Film::class);
```

## Defining allowed operations

### allowing()

Pass an array of `Allowed*` objects to declare what the client is permitted to filter, sort, include, or request sparse fieldsets for. Undeclared parameters are silently ignored.

```php
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;

JsonApiResponse::from(Film::class)
    ->allowing([
        AllowedFilter::similar('title'),
        AllowedFilter::exact('active'),
        AllowedSort::ascendant('created_at'),
        AllowedInclude::make('genre'),
    ]);
```

{% hint style="info" %}
For the full list of `Allowed*` classes and their options, see the [Requests](../requests/) section.
{% endhint %}

## Single resource responses

### gettingOne()

By default `JsonApiResponse` returns a paginated collection. Call `gettingOne()` to return a single `JsonApiResource` instead — the query is executed with `first()`:

```php
JsonApiResponse::from(Film::whereKey($id))->gettingOne();
```

## Exposing allowed rules to clients

### includeAllowedToResponse()

Append the declared allowed filters and sorts to the response `meta` object. Useful for frontend components that need to know what operations are supported:

```php
JsonApiResponse::from(Film::class)
    ->allowing([
        AllowedFilter::similar('title'),
        AllowedSort::ascendant('created_at'),
    ])
    ->includeAllowedToResponse();
```

The resulting response `meta` will contain:

```json
{
  "meta": {
    "current_page": 1,
    "total": 3,
    "allowed_filters": {
      "title": {
        "like": "*"
      }
    },
    "allowed_sorts": {
      "created_at": "asc"
    }
  }
}
```

Pass `false` to explicitly disable it even if `responses.include_allowed` is `true` in the config:

```php
->includeAllowedToResponse(false)
```

## Including ID attributes

### includingIdAttributes()

By default the primary key and any `*_id` foreign-key columns are stripped from `attributes`. Call `includingIdAttributes()` to keep them:

```php
JsonApiResponse::from(Film::class)->includingIdAttributes();
```

This temporarily overrides `apiable.responses.include_ids_on_attributes` for the current response.

## Force-appending attributes

### forceAppend()

Append model accessors or computed attributes to the response without requiring the client to declare them via the `append` query parameter. Useful for attributes that should always be present for a particular endpoint.

```php
// Append to the response model (uses the model class from `from()` / `using()`)
JsonApiResponse::from(Film::class)
    ->forceAppend(['is_featured', 'rating_label']);

// Append to a specific resource type
JsonApiResponse::from(Film::class)
    ->forceAppend('film', ['is_featured', 'rating_label']);

// Append to a specific model class (resolved to its resource type automatically)
JsonApiResponse::from(Film::class)
    ->forceAppend(Film::class, ['is_featured']);
```

### forceAppendWhen()

Conditionally force-append attributes. The condition can be a `bool` or a `Closure` that returns one:

```php
JsonApiResponse::from(Film::class)
    ->forceAppendWhen(
        fn () => auth()->user()?->isAdmin(),
        ['internal_notes', 'review_score']
    );

// Using a plain boolean
JsonApiResponse::from(Film::class)
    ->forceAppendWhen($request->has('admin'), Film::class, ['review_score']);
```

When the condition is falsy the method is a no-op and returns the response unchanged.

## Viewable scoping

### conditionallyLoadResults()

Toggle the viewable query scope on or off for the current response. See [Viewable Queries](viewable-queries.md) for how to implement the scope on your models.

```php
// Disable scoping for an admin endpoint
JsonApiResponse::from(Film::class)->conditionallyLoadResults(false);
```

The global default is controlled by `apiable.responses.viewable` in the config file.

## Custom pagination

### paginateUsing()

Supply a closure to replace the default pagination strategy entirely:

```php
JsonApiResponse::from(Film::class)
    ->paginateUsing(fn ($query) => $query->simplePaginate(10));
```

See [Pagination](pagination.md) for the built-in strategies and per-response convenience methods.

## Custom resource class

### usingResource()

Override the `JsonApiResource` subclass used to serialize the response. This applies to the top-level resource as well as every item in a paginated collection. Pass the fully-qualified class name of any class that extends `JsonApiResource`:

```php
use App\Http\Resources\FilmJsonApiResource;

JsonApiResponse::from(Film::class)
    ->usingResource(FilmJsonApiResource::class);
```

This is a per-response override. To register a custom resource class globally for a model (including included relationships), use `Apiable::modelResourceMap()`. See [Serialization — Custom resource classes](serialization.md#custom-resource-classes) for details.

## Content negotiation

### forceFormatting()

Override Accept-header negotiation and force a specific serialization format for this response:

```php
// Force JSON:API format regardless of Accept header
JsonApiResponse::from(Film::class)->forceFormatting();

// Force standard JSON
JsonApiResponse::from(Film::class)->forceFormatting('application/json');
```

See [Content Negotiation](content-negotiation.md) for full details.

## Full controller example

```php
<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

class FilmController extends Controller
{
    public function index(): JsonApiResponse
    {
        return JsonApiResponse::from(Film::class)
            ->allowing([
                AllowedFilter::similar('title'),
                AllowedFilter::exact('active'),
                AllowedSort::ascendant('created_at'),
                AllowedSort::descendant('created_at'),
                AllowedInclude::make('genre'),
            ])
            ->includeAllowedToResponse()
            ->forceAppendWhen(
                fn () => auth()->user()?->can('viewInternal', Film::class),
                ['internal_notes']
            );
    }

    public function show(Film $film): JsonApiResponse
    {
        return JsonApiResponse::from(Film::whereKey($film->id))
            ->allowing([
                AllowedInclude::make('genre'),
                AllowedInclude::make('cast'),
            ])
            ->gettingOne();
    }
}
```

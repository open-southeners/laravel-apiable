---
description: Transform your Eloquent models and collections into JSON:API format using multiple serialization approaches.
---

# Serialization

Laravel Apiable provides several ways to serialize your Eloquent models and collections into JSON:API format. Choose the approach that fits your use case.

## Apiable::toJsonApi()

The `Apiable` facade exposes a `toJsonApi()` method that accepts a variety of inputs and always returns a `JsonApiResource` or `JsonApiCollection` instance.

```php
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

// From an Eloquent model instance
Apiable::toJsonApi(Film::find(1));

// From a collection
Apiable::toJsonApi(Film::all());

// From a query builder (executes paginated query)
Apiable::toJsonApi(Film::where('active', true));

// From an existing paginator
Apiable::toJsonApi(Film::paginate(25));
```

Internally the method dispatches based on the input type:

| Input | Output |
|---|---|
| `Illuminate\Database\Eloquent\Builder` | Runs `JsonApiPaginator::paginate()`, returns `JsonApiCollection` |
| `Illuminate\Pagination\AbstractPaginator` | Wraps in `JsonApiCollection` |
| `Illuminate\Support\Collection` | Wraps in `JsonApiCollection` |
| `Illuminate\Database\Eloquent\Model` | Wraps in `JsonApiResource` |

{% hint style="info" %}
`Apiable::toJsonApi()` is a quick serialization helper. It does **not** apply filtering, sorting, pagination configuration, or include allowed rules. For those features use `JsonApiResponse` instead.
{% endhint %}

## Collection::toJsonApi()

The package registers a `toJsonApi()` macro on `Illuminate\Support\Collection`. Only items that implement the `JsonApiable` contract are included in the output.

```php
$collection = collect([
    Film::find(1),
    Film::find(2),
]);

return $collection->toJsonApi();
```

Items that do not implement `JsonApiable` are silently filtered out before wrapping in a `JsonApiCollection`.

## Model::toJsonApi() via HasJsonApi

Individual model instances can be serialized by using the `HasJsonApi` trait on your model. Your model must also implement the `JsonApiable` contract.

```php
use OpenSoutheners\LaravelApiable\Concerns\HasJsonApi;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use Illuminate\Database\Eloquent\Model;

class Film extends Model implements JsonApiable
{
    use HasJsonApi;
}
```

Then on any instance:

```php
$film = Film::find(1);

return $film->toJsonApi(); // returns JsonApiResource
```

This is a thin wrapper around `new JsonApiResource($this)`.

## When to use each approach

| Approach | Best for |
|---|---|
| `Apiable::toJsonApi()` | One-off serialization, simple controller responses, no query pipeline needed |
| `Collection::toJsonApi()` | Already-loaded collections where you want JSON:API output without a query |
| `HasJsonApi` trait | Single-model endpoints where you control the model instance directly |
| `JsonApiResponse` | Full query pipeline with filtering, sorting, includes, pagination, and allowed-rules enforcement |

## Custom resource types

By default the resource type is derived from the model class name in snake_case (e.g. `FilmGenre` becomes `film_genre`). You can override this globally in `config/apiable.php`:

```php
'resource_type_map' => [
    \App\Models\Film::class => 'film',
    \App\Models\User::class => 'client',
],
```

{% hint style="info" %}
Resource type names must comply with the [JSON:API member naming rules](https://jsonapi.org/format/#document-member-names).
{% endhint %}

## Example JSON:API output

A serialized `Film` model returns the following structure:

```json
{
  "data": {
    "id": "1",
    "type": "film",
    "attributes": {
      "title": "The Lost City",
      "description": "A gripping adventure...",
      "created_at": "2021-07-21T22:23:39.000000Z",
      "updated_at": "2021-02-01T08:52:39.000000Z"
    },
    "relationships": {}
  }
}
```

Primary keys and `*_id` foreign-key columns are excluded from `attributes` by default. See [JSON:API Response](json-api-response.md) for how to include them with `includingIdAttributes()`.

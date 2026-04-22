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

## Custom resource classes

By default the package serializes every model with the base `JsonApiResource` class. You can substitute your own subclass — globally per model, or for a single response.

### Global registry via `Apiable::modelResourceMap()`

Register a map of model classes to custom `JsonApiResource` subclasses in a service provider. The registry applies everywhere: top-level resources, collection items, and **included (side-loaded) related models**.

```php
use App\Http\Resources\FilmJsonApiResource;
use App\Http\Resources\UserJsonApiResource;
use App\Models\Film;
use App\Models\User;
use OpenSoutheners\LaravelApiable\Support\Apiable;

// In AppServiceProvider::boot()
Apiable::modelResourceMap([
    Film::class => FilmJsonApiResource::class,
    User::class => UserJsonApiResource::class,
]);
```

A custom resource class extends `JsonApiResource` and can override `withAttributes()` to add computed attributes:

```php
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

class FilmJsonApiResource extends JsonApiResource
{
    protected function withAttributes(): array
    {
        return [
            'rating_label' => $this->resource->getRatingLabel(),
        ];
    }
}
```

`withAttributes()` is merged on top of the model's regular attributes when serializing to JSON:API format.

### Resolving the resource class for a model

Use `Apiable::jsonApiResourceFor()` to retrieve the registered class for a model at runtime. Falls back to `JsonApiResource::class` when nothing is registered:

```php
$class = Apiable::jsonApiResourceFor($film); // e.g. FilmJsonApiResource::class
```

### Per-response override via `usingResource()`

To use a custom resource class for a single `JsonApiResponse` without touching the global registry, call `usingResource()`:

```php
use App\Http\Resources\FilmJsonApiResource;

JsonApiResponse::from(Film::class)
    ->usingResource(FilmJsonApiResource::class);
```

See [JsonApiResponse](json-api-response.md#custom-resource-class) for full details.

### Plain JSON serialization — `toApplicationJsonArray()`

When a client requests `application/json` rather than `application/vnd.api+json`, `JsonApiResource` now provides a `toApplicationJsonArray()` helper that merges model attributes with the computed attributes declared in `withAttributes()`:

```php
$resource = new FilmJsonApiResource(Film::find(1));

$array = $resource->toApplicationJsonArray();
// ['id' => 1, 'title' => 'The Lost City', 'rating_label' => 'PG-13', ...]
```

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

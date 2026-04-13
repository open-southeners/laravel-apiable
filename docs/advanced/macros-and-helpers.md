---
description: Reference for all builder macros, collection macros, and Apiable helper methods.
---

# Macros and Helpers

Laravel Apiable registers several macros on core Laravel classes and exposes a facade and global helper for common operations. This page is a complete reference for all of them.

{% hint style="info" %}
Builder macros are mixed into `Illuminate\Database\Eloquent\Builder` via `Builder::mixin()`. Collection macros are mixed into `Illuminate\Support\Collection`. Request macros are mixed into `Illuminate\Http\Request`. All are registered automatically when the service provider boots.
{% endhint %}

---

## Builder macros

These methods are available on any `Illuminate\Database\Eloquent\Builder` instance (i.e. the result of `Model::query()`, `Model::where(...)`, and so on).

**Source:** `OpenSoutheners\LaravelApiable\Builder`

### jsonApiPaginate

Paginate the query and return a `JsonApiCollection` formatted as a JSON:API response. The pagination strategy (length-aware, simple, or cursor) is controlled by the `apiable.responses.pagination.type` config value and can be overridden per-response with `simplePaginating()` / `cursorPaginating()` on `JsonApiResponse`.

| Parameter  | Type            | Default          | Description                                                 |
|------------|-----------------|------------------|-------------------------------------------------------------|
| `pageSize` | `int\|null`     | `null`           | Override the default page size from config.                 |
| `columns`  | `array`         | `['*']`          | Columns to select.                                          |
| `pageName` | `string`        | `'page.number'`  | Query string parameter name used for the page number.       |
| `page`     | `int\|null`     | `null`           | Override the current page (defaults to the request value).  |

```php
// Basic usage — uses config defaults
$collection = Post::query()->jsonApiPaginate();

// Custom page size and selected columns
$collection = Post::where('status', 'published')
    ->jsonApiPaginate(pageSize: 25, columns: ['id', 'title', 'slug']);
```

### hasJoin

Check whether a JOIN for the given table is already present on the query builder. Useful when building dynamic queries that may add the same JOIN multiple times.

| Parameter   | Type     | Description                       |
|-------------|----------|-----------------------------------|
| `joinTable` | `string` | The table name to look up.        |

Returns `bool`.

```php
$query = Post::query()->join('tags', 'tags.post_id', '=', 'posts.id');

if (! $query->hasJoin('tags')) {
    $query->join('tags', 'tags.post_id', '=', 'posts.id');
}
```

{% hint style="info" %}
`hasJoin` is used internally by `ApplySortsToQuery` to avoid duplicate JOIN clauses when sorting by a relationship attribute.
{% endhint %}

---

## Collection macros

These methods are available on any `Illuminate\Support\Collection` instance.

**Source:** `OpenSoutheners\LaravelApiable\Collection`

### toJsonApi

Convert a collection of `JsonApiable` model instances into a `JsonApiCollection`. Items that do not implement the `JsonApiable` contract are filtered out automatically. This method does not paginate — use `Builder::jsonApiPaginate()` when you need pagination.

```php
// From an Eloquent result set
$collection = Post::where('status', 'published')->get()->toJsonApi();

// From an ad-hoc collection
$collection = collect([Post::find(1), Post::find(2)])->toJsonApi();
```

---

## Request macros

These methods are available on `Illuminate\Http\Request`.

**Source:** `OpenSoutheners\LaravelApiable\Http\Request`

### wantsJsonApi

Returns `true` when the incoming request's `Accept` header is exactly `application/vnd.api+json`.

```php
use Illuminate\Http\Request;

public function index(Request $request)
{
    if ($request->wantsJsonApi()) {
        return Apiable::response(Post::query())->list();
    }

    return Post::paginate();
}
```

---

## Apiable facade and global helper

The `Apiable` facade (`OpenSoutheners\LaravelApiable\Support\Facades\Apiable`) and the `apiable()` global helper function expose the same methods. Use whichever style fits your codebase.

**Source:** `OpenSoutheners\LaravelApiable\Support\Apiable`

### config

Retrieve a value from the package's configuration. The key is automatically prefixed with `apiable.`.

| Parameter | Type     | Description                                    |
|-----------|----------|------------------------------------------------|
| `key`     | `string` | Dot-notation key relative to the `apiable` namespace. |

```php
// Facade
$operator = Apiable::config('requests.filters.default_operator');

// Helper
$operator = apiable()->config('requests.filters.default_operator');
```

### toJsonApi

Transform a model, collection, builder, or paginator into the appropriate JSON:API resource object (`JsonApiResource` or `JsonApiCollection`).

| Parameter  | Type                                                      |
|------------|-----------------------------------------------------------|
| `resource` | `Model`, `Collection`, `Builder`, `AbstractPaginator`, or any other value (returns an empty `JsonApiCollection`). |

```php
// Single model
$post = Post::find(1);

Apiable::toJsonApi($post);
apiable()->toJsonApi($post);

// Collection
$posts = Post::where('status', 'published')->get();

Apiable::toJsonApi($posts);
apiable()->toJsonApi($posts);

// Builder (paginates automatically)
Apiable::toJsonApi(Post::query());
```

### resourceTypeForModel

Derive the JSON:API resource type string from a model class or instance using snake_case of the class basename. This is the default guessing strategy used when no explicit mapping is registered.

| Parameter | Type                            |
|-----------|---------------------------------|
| `model`   | `Model` instance or class-string. |

```php
// Returns 'blog_post' for App\Models\BlogPost
Apiable::resourceTypeForModel(BlogPost::class);
apiable()->resourceTypeForModel(new BlogPost());
```

### getResourceType

Get the resource type for a model — returns the value from the registered type map if one exists, otherwise falls back to `resourceTypeForModel`.

| Parameter | Type                            |
|-----------|---------------------------------|
| `model`   | `Model` instance or class-string. |

```php
Apiable::getResourceType(Post::class);  // e.g. 'post'
Apiable::getResourceType(new Post());   // same result

apiable()->getResourceType(Post::class);
```

### jsonApiRenderable

Wrap a `Throwable` in a `Handler` that renders JSON:API-compliant error responses. Integrate this in your application's exception handler.

| Parameter    | Type            | Default  | Description                                        |
|--------------|-----------------|----------|----------------------------------------------------|
| `e`          | `Throwable`     | —        | The exception to handle.                           |
| `withTrace`  | `bool\|null`    | `null`   | Include stack trace in the response when `true`.   |

```php
// In App\Exceptions\Handler (Laravel 10 and below)
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

public function render($request, Throwable $e)
{
    return Apiable::jsonApiRenderable($e)->render($request);
}

// Helper form
apiable()->jsonApiRenderable($e)->render($request);
```

### response

Factory method for creating a `JsonApiResponse`. This is the primary way to build JSON:API list / show responses in controllers.

| Parameter  | Type                                    | Description                             |
|------------|-----------------------------------------|-----------------------------------------|
| `query`    | `Builder`, `Model`, or class-string.    | The data source for the response.       |
| `alloweds` | `array`                                 | Optional array of `Allowed*` objects.   |

```php
// Facade
return Apiable::response(Post::query())
    ->allowing([
        AllowedFilter::exact('status'),
        AllowedSort::field('created_at'),
    ])
    ->list();

// Helper
return apiable()->response(Post::query(), [
    AllowedFilter::exact('status'),
])->list();
```

### modelResourceTypeMap

Register one or more model-to-type mappings programmatically. Call this in a service provider's `boot` method. Accepts either a list of model class strings (types are guessed) or an associative array of `ModelClass => 'type-string'`.

| Parameter | Type    | Description                                                        |
|-----------|---------|--------------------------------------------------------------------|
| `models`  | `array` | Indexed array of model class strings, or associative `class => type` map. |

```php
// In AppServiceProvider::boot()

// Let the package guess the type names
Apiable::modelResourceTypeMap([
    App\Models\Post::class,
    App\Models\Tag::class,
]);

// Provide explicit type names
Apiable::modelResourceTypeMap([
    App\Models\Post::class => 'article',
    App\Models\Tag::class  => 'label',
]);

// Helper form
apiable()->modelResourceTypeMap([App\Models\Post::class => 'article']);
```

### getModelResourceTypeMap

Return the full model-to-type map that has been registered with `modelResourceTypeMap`.

```php
$map = Apiable::getModelResourceTypeMap();
// ['App\Models\Post' => 'article', 'App\Models\Tag' => 'label']

$map = apiable()->getModelResourceTypeMap();
```

### getModelFromResourceType

Reverse lookup: given a JSON:API type string, return the fully-qualified model class name, or `false` if no mapping exists.

| Parameter | Type     | Description              |
|-----------|----------|--------------------------|
| `type`    | `string` | The JSON:API type string. |

```php
$modelClass = Apiable::getModelFromResourceType('article');
// 'App\Models\Post'

$modelClass = apiable()->getModelFromResourceType('article');
```

### forceResponseFormatting

Force all JSON:API responses to use a specific format regardless of the `Accept` header. Useful for Inertia.js or other frontend frameworks that cannot send a custom `Accept` header.

| Parameter | Type           | Default  | Description                                                                 |
|-----------|----------------|----------|-----------------------------------------------------------------------------|
| `format`  | `string\|null` | `null`   | Format type (e.g. `'inertia'`). When `null`, uses the value from config. |

```php
// In AppServiceProvider::boot() or a middleware

Apiable::forceResponseFormatting();           // force with config-defined format
Apiable::forceResponseFormatting('inertia');  // force a specific format

apiable()->forceResponseFormatting('inertia');
```

{% hint style="warning" %}
`forceResponseFormatting` mutates the runtime config. Only call it once during the application bootstrap, or use a middleware that sets it conditionally per request.
{% endhint %}

---
description: Append computed model accessors to your JSON:API resource attributes.
---

# Appends

Appends allow API consumers to request computed [model accessors](https://laravel.com/docs/eloquent-mutators#defining-an-accessor) alongside the standard resource attributes. Unlike sparse fieldsets, appends do not affect the database `SELECT` — they are applied to the loaded model instances after the query completes.

## URL format

```
GET /posts?appends[post]=is_featured
GET /posts?appends[post]=is_featured,reading_time&appends[user]=avatar_url
```

The key inside `appends[]` is the JSON:API resource type. The value is a comma-separated list of accessor names.

## `AllowedAppends::make()`

`AllowedAppends::make()` takes a resource type and an array (or string) of accessor names:

```php
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;

AllowedAppends::make('post', ['is_featured', 'reading_time'])
AllowedAppends::make('user', ['avatar_url'])
```

Instead of a string resource type, you can pass the model class directly — the package resolves the type from your `resource_type_map` config:

```php
use App\Models\User;

AllowedAppends::make(User::class, ['avatar_url'])
```

## Allowing appends

{% tabs %}
{% tab title="Using methods" %}
Pass `AllowedAppends` instances to `allowing()`, or call `allowAppends()` directly:

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedAppends;
use App\Models\User;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedAppends::make('post', ['is_featured', 'reading_time']),
            AllowedAppends::make('user', ['avatar_url']),
        ]);
}
```

Using `allowAppends()` directly with a string type:

```php
return JsonApiResponse::from(Post::class)
    ->allowAppends('post', ['is_featured', 'reading_time'])
    ->allowAppends('user', ['avatar_url']);
```

Using a model class for the type argument:

```php
return JsonApiResponse::from(Post::class)
    ->allowAppends(User::class, ['avatar_url']);
```

Shorthand — pass an array as the first argument to target the main resource type:

```php
return JsonApiResponse::from(Post::class)
    ->allowAppends(['is_featured']);
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use App\Models\User;

#[AppendsQueryParam('post', ['is_featured', 'reading_time'])]
#[AppendsQueryParam(User::class, ['avatar_url'])]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`AppendsQueryParam` accepts: `type` (string resource type or model class-string), `attributes` (array of accessor names), and `description`.
{% endtab %}
{% endtabs %}

## Fields vs. appends

| | `allowFields()` | `allowAppends()` |
|---|---|---|
| Affects `SELECT` query | Yes — limits DB columns | No |
| Works with DB columns | Yes | No |
| Works with computed accessors | No | Yes |
| Applied at | Query build time | After query executes |

Use `allowFields()` for real database columns when you want to reduce data transfer. Use `allowAppends()` for PHP-computed values (accessors defined with `Attribute::make()` or `get*Attribute()` methods) that have no corresponding database column.

## Forcing appends unconditionally

If you want to always append specific accessors regardless of what the consumer requests, use `forceAppend()` on `JsonApiResponse`. See the [JsonApiResponse documentation](../responses/json-api-response.md) for details.

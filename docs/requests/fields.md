---
description: Select specific attributes per resource type using JSON:API sparse fieldsets.
---

# Fields

Sparse fieldsets allow API consumers to request only the columns they need from each resource type. This reduces response payload size and limits the columns included in the `SELECT` query sent to the database.

This feature follows the [JSON:API sparse fieldsets specification](https://jsonapi.org/format/#fetching-sparse-fieldsets).

## URL format

```
GET /posts?fields[post]=title,body
GET /posts?fields[post]=title,body&fields[user]=name,email
```

The key inside `fields[]` is the JSON:API resource type (e.g. `post`, `user`). The value is a comma-separated list of attribute names.

## `AllowedFields::make()`

`AllowedFields::make()` takes a resource type and an array (or comma-separated string) of allowed column names:

```php
use OpenSoutheners\LaravelApiable\Http\AllowedFields;

AllowedFields::make('post', ['title', 'body', 'created_at'])
AllowedFields::make('user', ['name', 'email'])
```

Instead of a string resource type, you can pass the model class directly — the package resolves the type from your `resource_type_map` config:

```php
use App\Models\User;

AllowedFields::make(User::class, ['name', 'email'])
```

## Allowing fields

{% tabs %}
{% tab title="Using methods" %}
Pass `AllowedFields` instances to `allowing()`, or call `allowFields()` directly:

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedFields;
use App\Models\User;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedFields::make('post', ['title', 'body', 'created_at']),
            AllowedFields::make('user', ['name', 'email']),
        ]);
}
```

Using `allowFields()` directly with a string type:

```php
return JsonApiResponse::from(Post::class)
    ->allowFields('post', ['title', 'body', 'created_at'])
    ->allowFields('user', ['name', 'email']);
```

Using a model class for the type argument:

```php
return JsonApiResponse::from(Post::class)
    ->allowFields(User::class, ['name', 'email']);
```

Shorthand — pass an array as the first argument to target the main resource type (in this case `post`):

```php
return JsonApiResponse::from(Post::class)
    ->allowFields(['title', 'body']);
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use App\Models\User;

#[FieldsQueryParam('post', ['title', 'body', 'created_at'])]
#[FieldsQueryParam(User::class, ['name', 'email'])]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`FieldsQueryParam` accepts: `type` (string resource type or model class-string), `fields` (array of column names), and `description`.
{% endtab %}
{% endtabs %}

## Primary key behaviour

The primary key (`id` by default) is **always included** in the `SELECT` query even if the consumer does not request it. This ensures JSON:API `id` fields are always present and relationships can be resolved correctly.

## Applying fields to included resources

When a consumer requests both sparse fieldsets and includes, the package applies the column restriction to the eager-loaded relationship query as well:

```
GET /posts?include=author&fields[post]=title&fields[user]=name
```

Only `title` is selected for `post` records, and only `name` is selected for eagerly loaded `user` records.

{% hint style="info" %}
Fields limit which columns are fetched from the database at the query level. If you need to include computed values that are not database columns, use [Appends](appends.md) instead.
{% endhint %}

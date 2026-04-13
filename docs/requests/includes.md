---
description: Eager-load relationships and include them as compound documents in your JSON:API responses.
---

# Includes

Includes let API consumers request related resources alongside the primary data. Included relationships are serialised as [compound documents](https://jsonapi.org/format/#document-compound-documents) in the top-level `included` array of the JSON:API response.

## URL format

```
GET /posts?include=author
GET /posts?include=author,tags
GET /posts?include=author.reviews
GET /posts?include=tags_count
```

Multiple relationships are separated by commas. Nested relationships use dot notation.

## `AllowedInclude::make()`

`AllowedInclude::make()` accepts a single relationship name, an array of names, or a dot-notation nested path:

```php
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;

AllowedInclude::make('author')
AllowedInclude::make('tags')
AllowedInclude::make('author.reviews')          // nested
AllowedInclude::make(['author', 'tags'])        // multiple at once
```

## Allowing includes

{% tabs %}
{% tab title="Using methods" %}
Pass `AllowedInclude` instances to `allowing()`, or call `allowInclude()` directly:

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\AllowedInclude;

public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedInclude::make('author'),
            AllowedInclude::make('tags'),
            AllowedInclude::make('author.reviews'),
        ]);
}
```

Using `allowInclude()` directly:

```php
return JsonApiResponse::from(Post::class)
    ->allowInclude('author')
    ->allowInclude('tags')
    ->allowInclude(AllowedInclude::make('author.reviews'));
```

You can also pass an array of relationship names to `allowInclude()`:

```php
return JsonApiResponse::from(Post::class)
    ->allowInclude(['author', 'tags']);
```
{% endtab %}

{% tab title="Using attributes" %}
```php
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

#[IncludeQueryParam('author')]
#[IncludeQueryParam('tags')]
#[IncludeQueryParam('author.reviews')]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```

`IncludeQueryParam` accepts: `relationships` (string or array of strings) and `description`.

You can also pass multiple relationships in a single attribute:

```php
#[IncludeQueryParam(['author', 'tags'])]
```
{% endtab %}
{% endtabs %}

## Nested includes

To allow consumers to request a relationship of a relationship, use dot notation:

```php
AllowedInclude::make('author.reviews')
```

This both allows the nested path and eager-loads `author.reviews` via a single `with('author.reviews')` call.

{% hint style="info" %}
The package enforces a `max_include_depth` limit (default: `3`) to prevent exponential relationship tree fan-out. You can adjust this in `config/apiable.php` under `responses.max_include_depth`.
{% endhint %}

## Count includes

Append `_count` to any relationship name to request a relationship count instead of the full related resources. The package calls `withCount()` on the query instead of `with()`:

```
GET /posts?include=tags_count
```

This adds a `tags_count` attribute to the resource's attributes without loading the actual `tags` records.

```php
AllowedInclude::make('tags_count')
```

No special configuration is required — the `_count` suffix is detected automatically by the package.

## Response shape

When the consumer requests `?include=author`, the response contains a top-level `included` array:

```json
{
  "data": [
    {
      "id": "1",
      "type": "post",
      "attributes": { "title": "Hello World" },
      "relationships": {
        "author": {
          "data": { "id": "5", "type": "user" }
        }
      }
    }
  ],
  "included": [
    {
      "id": "5",
      "type": "user",
      "attributes": { "name": "Taylor Otwell" }
    }
  ]
}
```

Duplicate resources across multiple includes are deduplicated automatically — each unique resource appears only once in the `included` array.

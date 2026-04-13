---
description: Configure pagination strategies for your JSON:API responses including length-aware, simple, and cursor-based pagination.
---

# Pagination

All `JsonApiResponse` list responses are paginated by default. The package wraps results in a `JsonApiCollection` that produces JSON:API-compliant `links` and `meta` objects.

## Query parameter format

Clients control pagination using dot-bracket notation:

```
GET /api/films?page[number]=2&page[size]=25
```

| Parameter | Description | Default |
|---|---|---|
| `page[number]` | Page number (1-based) | `1` |
| `page[size]` | Items per page | `responses.pagination.default_size` (50) |

## Pagination strategies

Three strategies are available, controlled by `config/apiable.php` or overridden per response.

### Length-aware (default)

Executes a `COUNT` query before fetching results. Returns full pagination metadata including the total item count, last page number, and page links.

```php
// config/apiable.php
'responses' => [
    'pagination' => [
        'default_size' => 50,
    ],
],
```

This is the default behavior — no extra configuration required.

### Simple

No `COUNT` query. Only knows whether a next or previous page exists. Useful when counting all rows is expensive and the total is not needed by the client.

```php
JsonApiResponse::from(Film::class)->simplePaginating();
```

Or set it globally:

```php
// config/apiable.php — set 'type' key (add it if missing)
'pagination' => [
    'type' => 'simple',
    'default_size' => 50,
],
```

### Cursor

Cursor-based pagination for large datasets. Avoids `OFFSET` queries entirely, making it efficient for deep pages. The client receives an opaque cursor instead of a page number.

```php
JsonApiResponse::from(Film::class)->cursorPaginating();
```

{% hint style="warning" %}
Cursor pagination requires the query to be sorted by a unique, sequential column (e.g. `id` or `created_at`). Without a deterministic sort order the cursor position is undefined.
{% endhint %}

## FastPaginate integration

When the [hammerstone/fast-paginate](https://github.com/hammerstonedev/fast-paginate) package is installed it is automatically detected and used inside `jsonApiPaginate`. No configuration is required — the package checks for both the `Hammerstone\FastPaginate\FastPaginate` and `AaronFrancis\FastPaginate\FastPaginate` class names at runtime.

```bash
composer require hammerstone/fast-paginate
```

After installation all length-aware paginated responses will use `fastPaginate()` instead of the standard paginator, which avoids expensive `COUNT` queries on large tables by using a subquery approach.

## The jsonApiPaginate builder macro

The package registers a `jsonApiPaginate` macro on `Illuminate\Database\Eloquent\Builder`. You can call it directly on any Eloquent builder if you need to paginate outside of `JsonApiResponse`:

```php
$collection = Film::where('active', true)->jsonApiPaginate();

// With explicit page size
$collection = Film::where('active', true)->jsonApiPaginate(pageSize: 10);

// Selecting specific columns
$collection = Film::jsonApiPaginate(columns: ['id', 'title', 'created_at']);
```

**Signature:**

```php
jsonApiPaginate(
    null|int|string $pageSize = null,
    array $columns = ['*'],
    string $pageName = 'page.number',
    ?int $page = null,
): JsonApiCollection
```

## Custom pagination logic

Use `paginateUsing()` on `JsonApiResponse` to replace the pagination mechanism entirely with your own closure:

```php
JsonApiResponse::from(Film::class)
    ->paginateUsing(function ($query) {
        return $query->simplePaginate(request()->integer('per_page', 20));
    });
```

The closure receives the Eloquent builder (or model instance) after all pipeline stages (filters, sorts, includes, fields) have been applied.

## Example JSON response

A length-aware paginated response looks like this:

```json
{
  "data": [
    {
      "id": "1",
      "type": "film",
      "attributes": {
        "title": "The Lost City",
        "created_at": "2021-07-21T22:23:39.000000Z"
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/films?page%5Bnumber%5D=1",
    "last": "http://localhost:8000/api/films?page%5Bnumber%5D=4",
    "prev": null,
    "next": "http://localhost:8000/api/films?page%5Bnumber%5D=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 4,
    "links": [
      { "url": null, "label": "&laquo; Previous", "active": false },
      { "url": "http://localhost:8000/api/films?page%5Bnumber%5D=1", "label": "1", "active": true },
      { "url": "http://localhost:8000/api/films?page%5Bnumber%5D=2", "label": "2", "active": false },
      { "url": null, "label": "Next &raquo;", "active": false }
    ],
    "path": "http://localhost:8000/api/films",
    "per_page": 50,
    "to": 50,
    "total": 183
  }
}
```

{% hint style="info" %}
The `links` URLs use percent-encoded bracket notation (`page%5Bnumber%5D`) which is equivalent to `page[number]`. Most HTTP clients decode these automatically.
{% endhint %}

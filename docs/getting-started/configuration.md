---
description: Full annotated reference for every option in config/apiable.php.
---

# Configuration

After publishing the configuration file with:

```bash
php artisan vendor:publish --provider="OpenSoutheners\LaravelApiable\ServiceProvider"
```

you will find `config/apiable.php` in your application. This page documents every available option.

---

## `resource_type_map`

| Type | Default |
|------|---------|
| `array` | `[]` |

Maps Eloquent model class names to JSON:API type strings. Every model you intend to expose through JSON:API responses should have an entry here.

```php
'resource_type_map' => [
    App\Models\Post::class => 'post',
    App\Models\User::class => 'user',
    App\Models\Tag::class  => 'tag',
],
```

If a model is omitted from the map, the package falls back to a snake_case version of the class basename (e.g. `BlogPost` → `blog_post`).

{% hint style="info" %}
You can also register the map programmatically via `Apiable::modelResourceTypeMap()`. See the [Installation](installation.md) page for details.
{% endhint %}

---

## `requests`

Options that govern how incoming query parameters are interpreted.

### `requests.validate_params`

| Type | Default |
|------|---------|
| `bool` | `false` |

When `true`, query parameters sent by the client (filters, sorts, includes, etc.) are validated against the allowed definitions you declare on your `JsonApiResponse`. Requests containing unknown or disallowed parameters will be rejected with a validation error rather than silently ignored.

```php
'requests' => [
    'validate_params' => true,
],
```

### `requests.filters.default_operator`

| Type | Default |
|------|---------|
| `int` | `AllowedFilter::SIMILAR` (`1`) |

The comparison operator applied when an `AllowedFilter` is created without an explicit operator. Available constants on `AllowedFilter`:

| Constant | Value | SQL equivalent |
|----------|-------|----------------|
| `SIMILAR` | `1` | `LIKE '%value%'` |
| `EXACT` | `2` | `= 'value'` |
| `SCOPE` | `3` | Eloquent scope |
| `LOWER_THAN` | `4` | `< value` |
| `LOWER_OR_EQUAL_THAN` | `5` | `<= value` |
| `GREATER_THAN` | `6` | `> value` |
| `GREATER_OR_EQUAL_THAN` | `7` | `>= value` |

```php
'requests' => [
    'filters' => [
        'default_operator' => \OpenSoutheners\LaravelApiable\Http\AllowedFilter::EXACT,
    ],
],
```

### `requests.filters.enforce_scoped_names`

| Type | Default |
|------|---------|
| `bool` | `false` |

When `true`, scope-based filters must be declared and sent with a `_scoped` suffix (e.g. `active_scoped` instead of `active`). This can help disambiguate filter names from attribute names in large APIs.

```php
'requests' => [
    'filters' => [
        'enforce_scoped_names' => true,
    ],
],
```

### `requests.sorts.default_direction`

| Type | Default |
|------|---------|
| `int` | `AllowedSort::BOTH` (`1`) |

The sort direction applied when an `AllowedSort` is created without an explicit direction. Available constants on `AllowedSort`:

| Constant | Value | Description |
|----------|-------|-------------|
| `BOTH` | `1` | Allows both ascending and descending |
| `ASCENDANT` | `2` | Ascending only |
| `DESCENDANT` | `3` | Descending only |

```php
'requests' => [
    'sorts' => [
        'default_direction' => \OpenSoutheners\LaravelApiable\Http\AllowedSort::ASCENDANT,
    ],
],
```

---

## `responses`

Options that control the shape and behavior of JSON:API responses.

### `responses.formatting.type`

| Type | Default |
|------|---------|
| `string` | `'application/vnd.api+json'` |

The default response `Content-Type` header. Changing this value affects how responses are formatted when no `Accept` header is present, or when `responses.formatting.force` is `true`.

```php
'responses' => [
    'formatting' => [
        'type' => 'application/vnd.api+json',
    ],
],
```

### `responses.formatting.force`

| Type | Default |
|------|---------|
| `bool` | `false` |

When `true`, every response uses the format defined in `responses.formatting.type`, regardless of the `Accept` header sent by the client. Useful when serving Inertia.js applications or other clients that do not negotiate content type.

{% hint style="warning" %}
Enabling `force` means the package will never return a 406 Not Acceptable response, even when a client explicitly requests an unsupported format. Only enable this if all your consumers accept the configured format.
{% endhint %}

```php
'responses' => [
    'formatting' => [
        'force' => true,
    ],
],
```

You can also enable forcing programmatically for a single request without touching the config file:

```php
use OpenSoutheners\LaravelApiable\Support\Apiable;

Apiable::forceResponseFormatting();

// Or force a specific format:
Apiable::forceResponseFormatting('application/json');
```

### `responses.normalize_relations`

| Type | Default |
|------|---------|
| `bool` | `false` |

When `true`, relationship names in JSON:API responses are converted to snake_case. For example, a relation named `authorProfile` would appear as `author_profile` in the response.

```php
'responses' => [
    'normalize_relations' => true,
],
```

### `responses.include_allowed`

| Type | Default |
|------|---------|
| `bool` | `false` |

When `true`, the response `meta` object includes a list of the filters, sorts, and includes that are permitted for the current endpoint. This is useful during development or for self-documenting APIs.

```php
'responses' => [
    'include_allowed' => true,
],
```

### `responses.pagination.default_size`

| Type | Default |
|------|---------|
| `int` | `50` |

The number of items returned per page when the client does not specify a `page[size]` parameter.

```php
'responses' => [
    'pagination' => [
        'default_size' => 25,
    ],
],
```

### `responses.viewable`

| Type | Default |
|------|---------|
| `bool` | `true` |

When `true`, the `ViewQueryable` scope is automatically applied to queries. This scope restricts results to resources the authenticated user is permitted to view. Disable it globally here, or override it per response using the `JsonApiResponse` API.

```php
'responses' => [
    'viewable' => false,
],
```

### `responses.include_ids_on_attributes`

| Type | Default |
|------|---------|
| `bool` | `false` |

When `true`, foreign key columns (fields ending with `_id`) are included in the resource `attributes` object of the JSON:API response. By default these are suppressed because JSON:API expresses relationships through `relationships` links rather than raw foreign key values.

```php
'responses' => [
    'include_ids_on_attributes' => true,
],
```

---

## `documentation`

Options for the `apiable:docs` Artisan command, which generates API documentation from your routes.

For the full reference on these settings, see the [Documentation Generator](../documentation/) section.

```php
'documentation' => [
    'output_path' => null, // defaults to storage_path('exports/apiable') at runtime

    'default_format' => 'markdown', // markdown | postman | openapi

    'default_stub' => 'protocol', // protocol | plain (markdown only)

    'excluded_routes' => [
        '_debugbar/*',
        '_ignition/*',
        'nova-api/*',
        'nova/*',
        'nova',
        'telescope*',
        'horizon*',
    ],

    'auth' => [
        'detect_middleware' => true,

        'middleware_map' => [
            'auth:sanctum' => 'bearer',
            'auth:api'     => 'bearer',
            'auth.basic'   => 'basic',
        ],
    ],
],
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `output_path` | `string\|null` | `null` | Directory where generated docs are written. Falls back to `storage_path('exports/apiable')` when `null`. |
| `default_format` | `string` | `'markdown'` | Output format: `markdown`, `postman`, or `openapi`. |
| `default_stub` | `string` | `'protocol'` | Markdown template style: `protocol` (opinionated JSON:API layout) or `plain` (minimal). Applies to markdown format only. |
| `excluded_routes` | `array` | See above | Route patterns excluded from documentation generation. Supports `*` wildcards. |
| `auth.detect_middleware` | `bool` | `true` | When `true`, the generator infers authentication type from route middleware. |
| `auth.middleware_map` | `array` | See above | Maps middleware names to authentication schemes (`bearer`, `basic`). |

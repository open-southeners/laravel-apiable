---
description: Customise the Markdown templates used for documentation generation.
---

# Customising Stubs

The Markdown exporter compiles its output from Blade templates called **stubs**. Two stubs ship with the package. You can publish them to your application and modify them freely — the command always prefers your published version over the package default.

## Publishing stubs

```bash
php artisan vendor:publish --tag=apiable-stubs
```

This copies the bundled stubs into your application:

```
stubs/
└── apiable/
    └── docs/
        ├── protocol.mdx   ← Tailwind Protocol MDX template
        └── plain.md       ← Plain Markdown template
```

## Stub resolution order

When the Markdown exporter runs it checks for a user-published stub **first**:

1. `{base_path}/stubs/apiable/docs/{stub}.{ext}` — your published stub (takes priority)
2. Package bundled stub — used when no published stub is found

If neither location has the requested stub, the command throws a `RuntimeException` with a hint to run `vendor:publish`.

{% hint style="info" %}
You only need to publish the stubs you want to customise. If you publish only `plain.md`, the `protocol` stub will continue to use the package version.
{% endhint %}

## Blade compilation

Stubs are compiled with **Laravel Blade**, so any Blade directive is available:

```blade
@foreach ($resource['endpoints'] as $endpoint)
    ## {{ $endpoint['title'] }}
    @if (!empty($endpoint['auth']))
    > Authentication required
    @endif
@endforeach
```

Each stub receives a single `$resource` variable — a plain PHP array with the complete data for one resource group.

## `$resource` array shape

```php
[
    // Human-readable group name (from #[DocumentedResource(name: '...')])
    'name' => 'Posts',

    // Group description (from #[DocumentedResource(description: '...')])
    'description' => 'Manage blog posts',

    // Fully-qualified Eloquent model class, or null if #[EndpointResource] is absent
    'modelClass' => 'App\\Models\\Post',

    // Array of endpoint arrays (one per documented controller action)
    'endpoints' => [
        [
            // Route URI without leading slash
            'uri' => 'posts',

            // HTTP method in uppercase
            'method' => 'GET',

            // Endpoint title (from #[DocumentedEndpointSection(title: '...')] or PHPDoc)
            'title' => 'List Posts',

            // Endpoint description (from attribute or PHPDoc first paragraph)
            'description' => 'Returns a paginated list of published posts.',

            // Auth scheme detected from middleware, or null for unauthenticated routes
            'auth' => [
                'type'       => 'bearer',        // 'bearer' or 'basic'
                'middleware' => 'auth:sanctum',  // the matched middleware name
            ],

            // Query parameters collected from *QueryParam attributes
            'queryParams' => [
                [
                    // Full query string key as it appears in the URL
                    'key'         => 'filter[title][like]',

                    // Param kind: filter | sort | include | fields | appends | search
                    'kind'        => 'filter',

                    // Description from the attribute's $description parameter
                    'description' => 'Filter by title (partial match)',

                    // Comma-separated allowed values, or '*' for any value
                    'values'      => '*',

                    // Whether the parameter is required (always false for query params)
                    'required'    => false,
                ],
                [
                    'key'         => 'sort',
                    'kind'        => 'sort',
                    'description' => 'Sort by creation date',
                    'values'      => 'created_at,-created_at',
                    'required'    => false,
                ],
                [
                    'key'         => 'include',
                    'kind'        => 'include',
                    'description' => 'Include related resources',
                    'values'      => 'tags,author',
                    'required'    => false,
                ],
                [
                    'key'         => 'fields[post]',
                    'kind'        => 'fields',
                    'description' => 'Sparse fieldset for posts',
                    'values'      => 'title,body,published_at',
                    'required'    => false,
                ],
                [
                    'key'         => 'appends[post]',
                    'kind'        => 'appends',
                    'description' => 'Append computed reading time',
                    'values'      => 'reading_time',
                    'required'    => false,
                ],
            ],
        ],
    ],
]
```

## Example: minimal plain stub

```blade
# {{ $resource['name'] }}

{{ $resource['description'] }}

@foreach ($resource['endpoints'] as $endpoint)

---

## {{ $endpoint['title'] }}

@if (!empty($endpoint['auth']))
> **Requires authentication** ({{ $endpoint['auth']['type'] }})
@endif

{{ $endpoint['description'] }}

`{{ $endpoint['method'] }}` `/{{ $endpoint['uri'] }}`

@if (!empty($endpoint['queryParams']))
| Parameter | Description |
|-----------|-------------|
@foreach ($endpoint['queryParams'] as $param)
| `{{ $param['key'] }}` | {{ $param['description'] ?: '—' }} |
@endforeach
@endif

@endforeach
```

{% hint style="warning" %}
After modifying a stub run `php artisan apiable:docs` to regenerate your documentation files. Blade views compiled from stubs are not cached between runs — the exporter passes `deleteCachedView: true` to `Blade::render()`.
{% endhint %}

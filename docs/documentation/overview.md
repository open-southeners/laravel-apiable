---
description: Generate API documentation from your annotated controllers with a single Artisan command.
---

# Documentation Generator: Overview

The `apiable:docs` command introspects your routes and controller PHP attributes to produce API documentation in three formats: **Postman v2.1**, **Markdown / MDX**, and **OpenAPI 3.1 YAML**. No separate spec file to maintain — your PHP code is the source of truth.

## How it works

1. The command scans all registered routes and skips any that match the configured exclusion patterns.
2. For each route whose controller carries a `#[DocumentedResource]` attribute, the generator reads the controller class and its action methods.
3. PHP attributes on the controller and its methods are converted into a structured `Resource` object containing `Endpoint` and `QueryParam` data.
4. The selected exporter(s) convert those objects to the target format and write the output files.

Controllers **without** `#[DocumentedResource]` are silently skipped, so you opt in only where you want documentation.

## Running the command

```bash
php artisan apiable:docs
```

When no `--format` flag is provided, the command checks `documentation.default_format` in the config file. If that is also unset, it presents an interactive prompt:

```
 ┌ Select output format ──────────────────────────────────────────┐
 │ › ● Markdown / MDX                                             │
 │   ○ Postman v2.1 Collection                                    │
 │   ○ OpenAPI 3.1 YAML                                           │
 └────────────────────────────────────────────────────────────────┘
```

### CLI options

| Option | Default | Description |
|---|---|---|
| `--format` | config or prompt | Output format: `markdown`, `postman`, `openapi`. Repeatable for multiple formats in one run. |
| `--stub` | `protocol` | Markdown stub to use: `protocol` (Tailwind Protocol MDX) or `plain` (portable Markdown). |
| `--only` | — | Only include routes whose URIs match these glob patterns. Repeatable. |
| `--exclude` | — | Exclude routes matching these patterns, merged with `documentation.excluded_routes` from config. |
| `--path` | config or `storage/exports/apiable` | Override the output directory. |

### Example commands

```bash
# Use the default format from config
php artisan apiable:docs

# Single format
php artisan apiable:docs --format=markdown

# Multiple formats in one run
php artisan apiable:docs --format=markdown --format=postman

# Plain Markdown (GitHub-friendly)
php artisan apiable:docs --format=markdown --stub=plain

# OpenAPI only for /api/* routes
php artisan apiable:docs --format=openapi --only="api/*"

# Custom output path
php artisan apiable:docs --format=postman --path=./docs/api
```

After a successful run the command prints a summary table:

```
 Format    Output file
 markdown  /storage/exports/apiable/posts.mdx
 markdown  /storage/exports/apiable/users.mdx
```

## Configuration

Publish the package config file if you have not already:

```bash
php artisan vendor:publish --tag=config
```

The `documentation` section in `config/apiable.php` controls all default behaviour:

```php
'documentation' => [

    // Where generated files are written. Defaults to storage_path('exports/apiable') at runtime.
    'output_path' => null,

    // Default format when --format is not supplied: markdown | postman | openapi
    'default_format' => 'markdown',

    // Default Markdown stub: protocol | plain
    'default_stub' => 'protocol',

    // Routes whose URIs match these glob patterns are excluded from output.
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
        // Auto-detect auth scheme from route middleware.
        'detect_middleware' => true,

        // Map middleware names to auth scheme types (bearer | basic).
        'middleware_map' => [
            'auth:sanctum' => 'bearer',
            'auth:api'     => 'bearer',
            'auth.basic'   => 'basic',
        ],
    ],

],
```

### Configuration reference

| Key | Default | Description |
|---|---|---|
| `output_path` | `null` (→ `storage/exports/apiable`) | Directory where generated files are written. |
| `default_format` | `markdown` | Format used when `--format` is not passed on the command line. |
| `default_stub` | `protocol` | Markdown stub used when `--stub` is not passed. |
| `excluded_routes` | _(see above)_ | Glob patterns for routes that should never appear in documentation. |
| `auth.detect_middleware` | `true` | Whether to inspect route middleware to detect authentication schemes. |
| `auth.middleware_map` | _(see above)_ | Maps middleware names to auth types (`bearer` or `basic`). |

{% hint style="info" %}
Add your own entries to `auth.middleware_map` for custom guards or third-party auth packages. Any middleware name not in the map is ignored during auth detection.
{% endhint %}

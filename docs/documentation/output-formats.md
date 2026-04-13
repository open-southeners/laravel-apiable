---
description: Detailed reference for each documentation output format: Postman, Markdown, and OpenAPI.
---

# Output Formats

The `apiable:docs` command supports three output formats. You can generate one or more in a single run using the `--format` flag.

{% tabs %}
{% tab title="Postman" %}
```bash
php artisan apiable:docs --format=postman
```
{% endtab %}
{% tab title="Markdown" %}
```bash
php artisan apiable:docs --format=markdown
```
{% endtab %}
{% tab title="OpenAPI" %}
```bash
php artisan apiable:docs --format=openapi
```
{% endtab %}
{% endtabs %}

## Postman v2.1 Collection

Produces a single `postman_collection.json` file importable into Postman or Insomnia.

### Output location

```
{output_path}/postman_collection.json
```

The collection name is derived from `config('app.name')` (e.g. `"My App Documentation"`).

### Structure

The collection follows the [Postman Collection v2.1 schema](https://schema.getpostman.com/json/collection/v2.1.0/collection.json). Each documented controller becomes a **folder** (item group), and each action method becomes a **request** item inside it.

```json
{
  "info": {
    "name": "My App Documentation",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "bearer",
    "bearer": [{ "key": "token", "value": "{{token}}", "type": "string" }]
  },
  "item": [
    {
      "name": "Posts",
      "description": "Manage blog posts",
      "item": [
        {
          "name": "List Posts",
          "request": {
            "method": "GET",
            "header": [
              { "key": "Accept", "value": "application/vnd.api+json" },
              { "key": "Content-Type", "value": "application/vnd.api+json" },
              { "key": "Authorization", "value": "Bearer {{token}}" }
            ],
            "url": {
              "raw": "{{baseUrl}}/posts",
              "host": ["{{baseUrl}}"],
              "path": ["posts"],
              "query": [
                {
                  "key": "filter[title][like]",
                  "value": "",
                  "description": "Filter by title (partial match)",
                  "disabled": false
                }
              ]
            }
          }
        }
      ]
    }
  ]
}
```

### Authentication

The exporter scans all endpoints and sets a **collection-level** `auth` block based on the first detected auth scheme. For each protected endpoint it also injects an `Authorization` header:

- **Bearer** (`auth:sanctum`, `auth:api`): `Authorization: Bearer {{token}}`
- **Basic** (`auth.basic`): `Authorization: Basic {{credentials}}`

The `{{baseUrl}}`, `{{token}}`, and `{{credentials}}` placeholders are Postman variables — set them in your Postman environment.

### URL path parameters

Route parameters such as `{post}` are converted to Postman-style `:post` path variables with an associated `variable` entry so Postman renders them as editable inputs.

---

## Markdown / MDX

Produces one file per documented controller (resource group). The file name is a URL-safe slug derived from the resource `name` attribute (e.g. `Posts` → `posts.mdx`).

### Output location

```
{output_path}/
  posts.mdx         ← protocol stub (default)
  users.mdx
```

or with `--stub=plain`:

```
{output_path}/
  posts.md
  users.md
```

### Stubs

Two stubs ship with the package:

| Stub | Flag | Extension | Best for |
|---|---|---|---|
| `protocol` | `--stub=protocol` | `.mdx` | [Tailwind Protocol](https://tailwindui.com/templates/protocol) documentation sites |
| `plain` | `--stub=plain` | `.md` | GitHub, GitLab, Bitbucket, or any Markdown-based static site |

The `protocol` stub uses `<Row>`, `<Col>`, `<Properties>`, `<Property>`, and `<CodeGroup>` JSX components that Protocol provides. The `plain` stub outputs a portable Markdown table of query parameters and a cURL example request.

### Blade compilation

Stubs are compiled with **Laravel Blade**. You can use any Blade directive inside them. The exporter passes a `$resource` array — see the [Customising Stubs](customising-stubs.md) page for the full variable reference.

{% hint style="info" %}
The exporter checks for user-published stubs at `stubs/apiable/docs/{stub}.{ext}` before falling back to the package bundled stubs. See [Customising Stubs](customising-stubs.md) for how to publish and modify them.
{% endhint %}

---

## OpenAPI 3.1 YAML

Produces a single `openapi.yaml` that validates against the [OpenAPI 3.1 specification](https://spec.openapis.org/oas/v3.1.0).

### Output location

```
{output_path}/openapi.yaml
```

### Structure

```yaml
openapi: '3.1.0'
info:
  title: My App Documentation
  version: 1.0.0
paths:
  /posts:
    get:
      summary: List Posts
      tags:
        - Posts
      description: Returns a paginated list of published posts.
      parameters:
        - name: 'filter[title][like]'
          in: query
          description: Filter by title (partial match)
          required: false
          schema:
            type: string
      security:
        - bearerAuth: []
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
```

### Security schemes

The exporter populates `components.securitySchemes` from detected auth middleware. Two scheme names are used:

| Scheme name | Type | Triggered by |
|---|---|---|
| `bearerAuth` | `http / bearer` | `auth:sanctum`, `auth:api` (or any `bearer` mapping) |
| `basicAuth` | `http / basic` | `auth.basic` (or any `basic` mapping) |

Each endpoint that requires authentication includes a per-operation `security` entry referencing the appropriate scheme.

### YAML serialisation

The exporter uses **`symfony/yaml`** when it is available (included with Laravel 12+). When it is not present it falls back to a built-in recursive encoder that handles the complete schema surface produced by this package.

---

## Authentication detection

All three formats share the same auth detection logic. When `documentation.auth.detect_middleware` is `true` (the default), the generator inspects the middleware registered on each route. Middleware names are matched against `documentation.auth.middleware_map`:

```php
'middleware_map' => [
    'auth:sanctum' => 'bearer',
    'auth:api'     => 'bearer',
    'auth.basic'   => 'basic',
],
```

Any middleware name found in the map produces the corresponding auth annotation in the output. Routes with no matching middleware are treated as unauthenticated.

To add support for a custom guard:

```php
'middleware_map' => [
    'auth:sanctum'   => 'bearer',
    'auth:api'       => 'bearer',
    'auth.basic'     => 'basic',
    'auth:my-guard'  => 'bearer', // custom entry
],
```

{% hint style="warning" %}
Setting `detect_middleware` to `false` disables auth detection entirely — no auth annotations will appear in any output format.
{% endhint %}

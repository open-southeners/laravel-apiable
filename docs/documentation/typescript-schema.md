---
description: Generate a typed TypeScript module describing every endpoint's allowed filters, sorts, includes, fields and appends.
---

# TypeScript Schema Export

The `apiable:types` command reads the same controller PHP attributes as [`apiable:docs`](overview.md) and emits a single TypeScript module: an `EndpointSchema` interface plus an `apiSchema` const object, keyed by JSON:API resource type slug. It's designed to feed a typed URL builder (such as `@open-southeners/flex-url` v2) so the frontend gets compile-time knowledge of which filters, sorts, includes, sparse fieldsets and appends each endpoint actually allows.

## Running the command

```bash
php artisan apiable:types
```

By default the file is written to `resources/js/api-schema.ts` (relative to the application base path).

### CLI options

| Option | Default | Description |
|---|---|---|
| `--path` | `resources/js/api-schema.ts` | Override the output file path. |
| `--only` | — | Only include routes whose URIs match these glob patterns. Repeatable. |
| `--exclude` | — | Exclude routes matching these patterns, merged with `documentation.excluded_routes` from config. |

```bash
# Custom output location
php artisan apiable:types --path=resources/js/generated/api-schema.ts

# Only generate schema for /api/* routes
php artisan apiable:types --only="api/*"
```

## How it works

1. The command scans all registered routes and skips any that match `documentation.excluded_routes` (the same config the docs generator uses) or an `--exclude` pattern.
2. For each controller carrying a `#[DocumentedResource]` attribute, it picks the route that best represents that resource's **collection/list** endpoint — the `index` action when present, otherwise the route with the fewest bound parameters — since filters/sorts/includes/fields/appends only make sense against a listing endpoint.
3. `#[FilterQueryParam]`, `#[SortQueryParam]`, `#[IncludeQueryParam]`, `#[FieldsQueryParam]`, `#[AppendsQueryParam]`, `#[ApplyDefaultSort]` and `#[ApplyDefaultFilter]` attributes on that controller (class-level and the chosen method) are read directly — see [Annotating Controllers](annotating-controllers.md) for the attribute reference — and merged into one schema entry per resource.
4. The resource's JSON:API type slug (the object key, and the `resource` field) comes from `#[EndpointResource]`'s model class resolved through `Apiable::getResourceType()` — the same resolution `appends[...]`/`fields[...]` documentation keys use.

Controllers **without** `#[DocumentedResource]` are silently skipped, exactly like `apiable:docs`. A documented controller whose action has no query-param attributes at all still gets an entry — just with empty `filters`/`sorts`/`includes`/`fields`/`appends` — so a caller can always expect a schema for every documented resource.

## Output shape

```ts
export interface EndpointSchema {
  resource: string;
  path: string;
  filters: Record<string, { operators: Array<'equal' | 'like' | 'gt' | 'gte' | 'lt' | 'lte' | 'scope'>; values?: string[] }>;
  sorts: string[];
  includes: string[];
  fields: Record<string, string[]>;
  appends: Record<string, string[]>;
  defaultSort?: string;
  defaultFilters?: Record<string, string>;
}

export const apiSchema = {
  issues: {
    resource: 'issues',
    path: '/api/v1/issues',
    filters: {
      status: { operators: ['equal'], values: ['open', 'closed'] },
      due_at: { operators: ['gte', 'lte'] },
    },
    sorts: ['created_at', 'priority', 'project.name'],
    includes: ['project', 'assignee', 'labels'],
    fields: {},
    appends: {
      issues: ['is_overdue'],
    },
    defaultSort: '-created_at',
  },
} as const;
```

Every object is keyed alphabetically (top-level resource slugs, filter attributes, `fields`/`appends` type slugs) so regenerating the file after an unrelated code change produces a minimal diff.

{% hint style="warning" %}
**Operator naming**: `AllowedFilter::EXACT` serialises to `equal` on the wire (`filter[attr][equal]=value` — see `AllowedFilter::operatorKey()`), not `eq`. The `operators` union above reflects the real wire format rather than a shorter alias, so any consumer typed against `EndpointSchema` must use `'equal'` for exact-match filters.
{% endhint %}

- `filters[attr].operators` lists every operator constant registered for that attribute across all `#[FilterQueryParam]` attributes (e.g. `AllowedFilter::GREATER_OR_EQUAL_THAN` + `AllowedFilter::LOWER_OR_EQUAL_THAN` on `due_at` merges into `['gte', 'lte']`, matching the multi-operator range-filter support described in [Filters](../requests/filters.md)).
- `filters[attr].values` is omitted entirely when the filter is unrestricted (`values: '*'`, the attribute default); it's only present when the attribute is restricted to an explicit value list.
- `defaultSort` is derived from `#[ApplyDefaultSort]` (`-attribute` for `DefaultSort::DESCENDANT`, otherwise `attribute`); `defaultFilters` from `#[ApplyDefaultFilter]`, keyed by attribute with the default value as a plain string. Both keys are omitted when the controller declares neither attribute.

## Known limitation: fluent-style controllers

Like `apiable:docs`, `apiable:types` only reads PHP attributes — a controller that configures allowances fluently (`JsonApiResponse::allowFilter()`, `->allowing([...])`, etc.) instead of declaring `#[FilterQueryParam]`-style attributes still gets a schema entry (route + resource are always picked up), but with empty `filters`/`sorts`/`includes`/`fields`/`appends`. There's no way to see what a fluent call chain allows without executing it; a future runtime-introspection mode is tracked but not implemented. Annotate any controller you want typed in the generated schema.

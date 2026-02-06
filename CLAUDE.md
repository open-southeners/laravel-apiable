# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel Apiable is a Laravel package that integrates JSON:API resources into Laravel API projects. It provides a fluent interface for building JSON:API-compliant responses with support for filtering, sorting, includes, sparse fieldsets, pagination, and fulltext search.

**Official Documentation**: https://docs.opensoutheners.com/laravel-apiable/

## Development Commands

### Testing
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Http/JsonApiResponseTest.php

# Run tests with coverage
vendor/bin/phpunit --configuration phpunit.coverage.dist.xml
```

### Code Quality
```bash
# Fix code style (Laravel Pint)
vendor/bin/pint

# Run static analysis (PHPStan level 5)
vendor/bin/phpstan analyse

# Check type coverage
vendor/bin/phpstan analyse --configuration phpstan.neon
```

## Architecture

### Core Pipeline Flow

All JSON:API responses flow through a 5-stage pipeline architecture:

1. **Entry Point**: `Apiable::toJsonApi($resource)` dispatches based on resource type
2. **Query Building**: `JsonApiResponse` sends request through pipeline stages:
   - `ApplyFulltextSearchToQuery` - Laravel Scout integration
   - `ApplyFiltersToQuery` - Query filtering with operators
   - `ApplyIncludesToQuery` - Eager load relationships
   - `ApplyFieldsToQuery` - Sparse fieldsets (select specific columns)
   - `ApplySortsToQuery` - Order results (supports relationship sorting via JOINs)
3. **Pagination**: `Builder::jsonApiPaginate()` macro executes query with pagination strategy
4. **Serialization**: Resources transform to JSON:API format
5. **Post-processing**: `IteratesResultsAfterQuery` adds appends to resources and includes

### Key Components

**Builder Macros** (registered via `ServiceProvider::registerMacros()`):
- `Builder::jsonApiPaginate()` - Custom pagination with FastPaginate support
- `Builder::buildLengthAwarePaginator()` - Traditional pagination with COUNT query
- `Builder::hasJoin()` - Check if JOIN already exists on query

**Resource Classes**:
- `JsonApiResource` - Single model transformation
- `JsonApiCollection` - Collection/paginator transformation
- Traits: `RelationshipsWithIncludes`, `CollectsWithIncludes`, `IteratesResultsAfterQuery`

**Request Handling**:
- `RequestQueryObject` - Stores query builder and request parameters
- `JsonApiResponse` - Main entry point implementing `Responsable` interface
- Allowed* classes - Define filtering/sorting/field selection rules

### Laravel Mixin Pattern

This package extensively uses Laravel's mixin pattern. Builder methods in `src/Builder.php` return closures that are mixed into `Illuminate\Database\Eloquent\Builder`:

```php
// In Builder.php
public function jsonApiPaginate() {
    return function (/* params */) {
        // Closure becomes a method on Eloquent\Builder
        // $this refers to the Builder instance
    };
}
```

### Pagination Strategies

Three pagination modes controlled by `config('apiable.responses.pagination.type')`:

1. **length-aware** (default) - Executes COUNT query, returns total count
2. **simple** - No COUNT query, only knows if next page exists
3. **cursor** - Cursor-based pagination for large datasets

Convenience methods to override per-response:
- `$response->simplePaginating()` - Sets config to 'simple'
- `$response->cursorPaginating()` - Sets config to 'cursor'

### Performance Optimizations (Applied Feb 2026)

Recent optimizations for large dataset handling:

1. **Hash-set deduplication**: `RelationshipsWithIncludes` and `CollectsWithIncludes` use `$includedIndex` array with O(1) isset() lookups instead of O(N*M²) Collection::unique()
2. **Eliminated double serialization**: `CollectsWithIncludes` iterates `$this->collection` directly instead of calling toArray()
3. **Flat appends iteration**: `IteratesResultsAfterQuery` single-pass over deduplicated includes
4. **Depth limiting**: `max_include_depth` config (default 3) prevents exponential relationship tree fan-out

## Configuration

Package config at `config/apiable.php`:

- `resource_type_map` - Map Eloquent models to JSON:API resource types
- `requests.filters.default_operator` - Default filter operator (e.g., SIMILAR, EQUAL)
- `responses.pagination.default_size` - Default page size (50)
- `responses.pagination.type` - Pagination strategy
- `responses.max_include_depth` - Max relationship nesting depth
- `responses.viewable` - Enable viewable query scoping

**Per-response config override pattern**: Use `config(['apiable.key' => value])` inside convenience methods to override without mutating global config.

## Testing

**Test Structure**:
- Tests in `tests/` directory
- Fixtures: `tests/Fixtures/` (Post, Tag, User, Plan models)
- Migrations: `tests/database/`
- PHPUnit 11 with Orchestra Testbench

**Running subset of tests**: PHPUnit standard filtering works:
```bash
vendor/bin/phpunit --filter testMethodName
vendor/bin/phpunit tests/Http/
```

## Code Style Requirements

- **Laravel Pint** with Laravel preset (enforced)
- **PHPStan level 5** for static analysis
- Type coverage minimums: 20% for returns, params, properties
- `src/Builder.php` excluded from PHPStan (mixin closures pattern)

## Important Patterns

**Trait-based resource construction**:
- `RelationshipsWithIncludes::addIncluded()` - Recursively collects nested includes
- `CollectsWithIncludes::withIncludes()` - Aggregates includes across collection
- Both traits use hash-set deduplication for performance

**Config-driven behavior**:
- Many features check config at runtime (pagination type, viewable queries, etc.)
- Convenience methods temporarily override config using `config(['key' => value])`

**Response format negotiation**:
- Checks `Accept` header: `application/vnd.api+json` → JSON:API format
- Falls back to `config('apiable.responses.formatting.type')` for Inertia/forced mode
- Throws 406 for unsupported Accept headers

## Contribution Workflow

1. Check for existing issue or create one first
2. Fork repository and create descriptive branch
3. Write code following Laravel Pint standards
4. **All tests must pass** - add tests for new functionality
5. Submit PR to `main` branch

Commits must have descriptive messages (no enforced format).

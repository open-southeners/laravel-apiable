---
description: A Laravel package that integrates JSON:API resources into your API projects with filtering, sorting, includes, pagination, and more.
---

# Introduction

**Laravel Apiable** brings the [JSON:API specification](https://jsonapi.org/) to your Laravel application. It builds on top of Laravel's existing API resources, Eloquent query builder, and request handling -- so you keep using the tools you already know.

## Features

- **JSON:API serialization** -- Transform models and collections into spec-compliant responses
- **Filtering** -- 7 filter operators including LIKE, exact match, comparison operators, and query scopes
- **Sorting** -- Sort by attributes or relationships with automatic JOIN handling
- **Includes** -- Eager-load and embed related resources as compound documents
- **Sparse fieldsets** -- Let clients select only the fields they need
- **Appends** -- Expose computed model accessors on demand
- **Full-text search** -- Laravel Scout integration with search filters
- **Pagination** -- Length-aware, simple, or cursor-based strategies with FastPaginate support
- **Content negotiation** -- Automatic format switching based on Accept headers
- **Error handling** -- Render exceptions in JSON:API error format
- **Testing utilities** -- Fluent assertion helpers for JSON:API responses
- **Documentation generator** -- Export Postman, Markdown, and OpenAPI docs from controller attributes

## Requirements

- PHP 8.1+
- Laravel 10+

## Quick start

```bash
composer require open-southeners/laravel-apiable
```

Then head to the [Installation](getting-started/installation.md) guide to set up your models and configuration.

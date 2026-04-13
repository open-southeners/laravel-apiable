---
description: Scope API queries by user access using viewable query scopes on your models.
---

# Viewable Queries

Viewable queries let you automatically restrict which resources a request returns based on who is making it. When enabled, `JsonApiResponse` calls a scope on your model or query builder before executing the query — so unauthenticated users or users without access simply never see those records.

## How it works

After the request pipeline (filters, sorts, includes, fields) runs, `JsonApiResponse` checks:

1. Whether `apiable.responses.viewable` is `true` (the default).
2. Whether the model implements `ViewQueryable` **or** the query builder implements `ViewableBuilder`.

If both conditions are true the scope is applied with the currently authenticated user, which may be `null` for unauthenticated requests.

## ViewQueryable — model-level scope

Implement `ViewQueryable` on your Eloquent model and add a `scopeViewable` method. The method receives the builder and the optional authenticated user.

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Contracts\ViewQueryable;

class Film extends Model implements JsonApiable, ViewQueryable
{
    /**
     * Scope applied to the query for show/hide items.
     */
    public function scopeViewable(Builder $query, ?Authenticatable $user = null): void
    {
        if ($user === null) {
            // Unauthenticated: only show publicly available films
            $query->where('public', true);

            return;
        }

        // Authenticated: show films the user owns or that are public
        $query->where(function (Builder $q) use ($user) {
            $q->where('public', true)
              ->orWhereHas('author', fn (Builder $q) => $q->whereKey($user->getKey()));
        });
    }
}
```

Laravel calls `scopeViewable` as a local query scope, so the method name must be prefixed with `scope`.

## ViewableBuilder — custom builder scope

If your model uses a custom Eloquent builder, implement `ViewableBuilder` on that builder class instead. The `viewable()` method receives the optional authenticated user and must return the builder for chaining.

```php
<?php

namespace App\Builders;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModelClass>
 */
class FilmBuilder extends Builder implements ViewableBuilder
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @return \Illuminate\Database\Eloquent\Builder<TModelClass>
     */
    public function viewable(?Authenticatable $user = null): static
    {
        if ($user === null) {
            return $this->where('public', true);
        }

        return $this->where(function (Builder $q) use ($user) {
            $q->where('public', true)
              ->orWhereHas('author', fn (Builder $q) => $q->whereKey($user->getKey()));
        });
    }
}
```

Then tell your model to use this builder:

```php
class Film extends Model implements JsonApiable
{
    public function newEloquentBuilder($query): FilmBuilder
    {
        return new FilmBuilder($query);
    }
}
```

`JsonApiResponse` detects `ViewableBuilder` on the query instance automatically — no additional interface on the model is needed.

## Global configuration

The viewable feature is **enabled by default**. To disable it globally, set the config option to `false`:

```php
// config/apiable.php
'responses' => [
    'viewable' => false,
],
```

## Per-request toggle

### conditionallyLoadResults()

Override the global setting for a single response using `conditionallyLoadResults()`:

```php
// Disable viewable scoping for an admin-only endpoint
JsonApiResponse::from(Film::class)
    ->conditionallyLoadResults(false);

// Re-enable if you have disabled it globally but need it for one endpoint
JsonApiResponse::from(Film::class)
    ->conditionallyLoadResults(true);
```

This is particularly useful for admin controllers where authenticated staff should see all records:

```php
class AdminFilmController extends Controller
{
    public function index(): JsonApiResponse
    {
        return JsonApiResponse::from(Film::class)
            ->allowing([
                AllowedFilter::exact('active'),
            ])
            ->conditionallyLoadResults(false); // admins see everything
    }
}
```

{% hint style="warning" %}
`conditionallyLoadResults()` modifies the `apiable.responses.viewable` config value in-place for the duration of the current request. If you run multiple responses in a single request lifecycle (uncommon) ensure each one sets its own value.
{% endhint %}

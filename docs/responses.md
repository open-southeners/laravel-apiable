---
description: >-
  For your API controllers responses you've multiple ways to transform your
  models or collection of models to JSON:API, here we list all of them.
---

# Responses

To start using the JSON:API serialisation (responses) you can just use the Apiable facade to do so:

```php
Apiable::toJsonApi(Film::all());
```

{% hint style="info" %}
Take in mind that this `toJsonApi` method doesn't do anything more than serialisation, if you want filters, sorts and more flexible queries for users on your API head to [Requests section](requests.md).
{% endhint %}

## Custom resource type

To customise the resource type, the one that you see as the `type: "post"` (in case of a Post model), **this is very important for your frontend** to identify the resource. If you want to customise this you will need the `config/apiable.php` file on your Laravel app:

```
php artisan vendor:publish --provider="OpenSoutheners\\LaravelApiable\\ServiceProvider"
```

Then add items to the `resource_type_map` option:

```php
<?php

return [

    'resource_type_map' => [
        \App\Models\Film::class => 'film',
        \App\Models\User::class => 'client',
    ],

```

{% hint style="info" %}
Just remember to check the allowed types in [the oficial JSON:API spec](https://jsonapi.org/format/#document-member-names).
{% endhint %}

## Using JsonApiResponse

JsonApiResponse is a class helper that will abstract everything for you:

1. Handle users request query parameters sent (filters, sorts, includes, appends, etc).
2. Transform all request parameters to a Eloquent query then apply conditional viewable and pagination if needed.
3. Serialisation depending on application needs (JSON:API, raw JSON, etc).

### List of resources

{% hint style="info" %}
This will get a paginated response. In case you've install [hammerstone/fast-paginate](https://github.com/hammerstonedev/fast-paginate) it will use fastPaginate method to make it faster.
{% endhint %}

To get a list (wrapped in a `JsonApiCollection`) of your resources query you should do the following:

```php
JsonApiResponse::from(Film::where('title', 'LIKE', 'The%'));
```

#### Conditionally viewable resources

By default this is enabled but it can be disabled from the config file.

When listing resources normally through API you want to exclude some of the ones that your users doesn't have access to, and we got covered here, simply adding a query scope in your model:

```php
<?php

namespace App\Models;

use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Contracts\QueryViewable;

class Film extends Model implements JsonApiable, QueryViewable
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return void
     */
    public function scopeViewable(Builder $query, ?Authenticatable $user = null)
    {
        $query->whereHas('author', fn (Builder $query) => $query->whereKey($user->getKey()));
    }
}
```

In case your models are using custom query builders you can use this feature as well on them:

```php
<?php

namespace App\Builders;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @extends Builder<TModelClass>
 */
class FilmBuilder extends Builder implements ViewableBuilder
{
    /**
     * Scope applied to the query for show/hide items.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function viewable(?Authenticatable $user = null)
    {
        $this->whereHas('author', fn (Builder $query) => $query->whereKey($user->getKey()));
        
        return $this;
    }
}
```

#### Disable viewable per request

If the **viewable is implemented at the model or query builder** level **this will get called** whenever you use Apiable, you can disable it per request using the following method:

```php
JsonApiResponse::from(Film::where('title', 'LIKE', 'The%'))
    ->conditionallyLoadResults(false);
```

#### Customise pagination method

In case you want to customise the pagination used you can actually use the `paginateUsing` method:

```php
JsonApiResponse::from(Film::class)
    ->paginateUsing(fn ($query) => $query->simplePaginate());
```

### One resource from the list or query

You can still use apiable responses to get one result:

```php
JsonApiResponse::from(Film::whereKey($id))->gettingOne();
```

This will get a JsonApiResource response with just that one resource queried.

## Responses formatting

{% hint style="info" %}
Custom formatting is coming soon on v4.
{% endhint %}

Serialisation is something this package was limited into back when it was dedicated just to JSON:API, nowadays and in the future it is capable of more than that so you can still use the powerful requests query parameters with the responses your frontend or clients requires.

So in case they want normal JSON they should send the following header:

```
Accept: application/json
```

Or in case you want JSON:API:

```
Accept: application/vnd.api+json
```

In case you want to force any formatting in your application you can use the following:

```php
JsonApiResponse::from(Film::class)->forceFormatting();
```

The previous will force formatting to the default format which is in the config file generated by this package (on `config/apiable.php`), in case you want to enforce other format you only need to specify its RFC language string:

```php
JsonApiResponse::from(Film::class)->forceFormatting('application/json');
```

{% hint style="warning" %}
In case an unsupported type is sent via the `Accept` header or this `forceFormatting` method the application will return a 406 not acceptable HTTP exception.
{% endhint %}

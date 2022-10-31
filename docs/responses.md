---
layout: default
title: Responses
category: Introduction
---

# Responses

For your API controllers responses you've multiple ways to transform your models or collection of models to JSON:API, here we list all of them.

## Custom resource type

To customise the resource type, the one that you see as the `type: "post"` (in case of a Post model), **this is very important for your frontend** to identify the resource. If you want to customise this:

1. Add `OpenSoutheners\LaravelApiable\Contracts\JsonApiable` contract to the model class.
2. Then add `jsonApiableOptions` method to the model returning the type as a string.

```php
<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;

class Post extends Model implements JsonApiable
{
    /**
     * Set options for model to be serialize with JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\JsonApiableOptions
     */
    public function jsonApiableOptions()
    {
        return JsonApiableOptions::withDefaults(self::class)
            ->resourceType('publication');
    }
}
```

::: tip
Just remember to check the allowed types in [the oficial JSON:API spec](https://jsonapi.org/format/#document-member-names).
:::

## Custom API resource class

Adding the `transformer` to your model's `jsonApiableOptions` method which needs to point to an API resource that extends `JsonApiResource`:

```php
<?php

namespace OpenSoutheners\LaravelApiable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use App\Http\Resources\PostResource;

class Post extends Model implements JsonApiable
{
    /**
     * Set options for model to be serialize with JSON:API.
     *
     * @return \OpenSoutheners\LaravelApiable\JsonApiableOptions
     */
    public function jsonApiableOptions()
    {
        return JsonApiableOptions::withDefaults(self::class)
            ->transformer(PostResource::class);
    }
}
```

Also your JSON:API resource class should look like this:

```php
<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

class PostResource extends JsonApiResource
{
    /**
     * Attach additional attributes data.
     *
     * @return array
     */
    protected function withAttributes()
    {
        return [
            'is_first_visit' => $this->last_accessed_at === null,
            $this->mergeWhen(Auth::user() instanceof User && $this->author->id === Auth::id(), [
                'is_author' => true,
            ]),
        ];
    }
}
```

## Using JsonApiResponse to create API responses

::: tip
For the requests side with features like allowing specific filters, sorts, etc., you should [check our Request section](requests.md).
:::

### List of resources

::: tip
This will get a paginated response. In case you've install [hammerstone/fast-paginate](https://github.com/hammerstonedev/fast-paginate) it will use fastPaginate to make it faster.
:::

To get a list (wrapped in a `JsonApiCollection`) of your resources query you should do the following:

```php
JsonApiResponse::from(Film::where('title', 'LIKE', 'The%'))->list();
```

### One resource by key

And to get a single resource you can do the following:

```php
JsonApiResponse::from(Film::class)->getOne(1);
```

But **you are not only limited to send a key**, you could also send a model instance, as long as it has the key (`id` by default in Laravel) available:

```php
$film = Film::first();

JsonApiResponse::from(Film::class)->getOne($film);
```
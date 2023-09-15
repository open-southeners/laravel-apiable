---
description: >-
  For your API controllers responses you've multiple ways to transform your
  models or collection of models to JSON:API, here we list all of them.
---

# Responses

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

{% hint style="info" %}
Just remember to check the allowed types in [the oficial JSON:API spec](https://jsonapi.org/format/#document-member-names).
{% endhint %}

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

### List of resources

{% hint style="info" %}
This will get a paginated response. In case you've install [hammerstone/fast-paginate](https://github.com/hammerstonedev/fast-paginate) it will use fastPaginate method to make it faster.
{% endhint %}

To get a list (wrapped in a `JsonApiCollection`) of your resources query you should do the following:

```php
JsonApiResponse::from(Film::where('title', 'LIKE', 'The%'));
```

In case you want to customise the pagination used you can actually use the `paginateUsing` method:

```php
JsonApiResponse::paginateUsing(fn ($query) => $query->simplePaginate());
```

### One resource by key

You can still use apiable responses to get one result:

```php
JsonApiResponse::from(Film::class)->gettingOne();
```


---
description: Prepare your Eloquent models for JSON:API serialization.
---

# Model Setup

Before your Eloquent models can be serialized to JSON:API format, each model must implement the `JsonApiable` contract and use the `HasJsonApi` trait.

## Implementing the interface and trait

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Concerns\HasJsonApi;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;

class Post extends Model implements JsonApiable
{
    use HasJsonApi;

    protected $fillable = ['title', 'content', 'status'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
```

- `JsonApiable` is the contract that marks the model as JSON:API-serializable and requires a `toJsonApi()` method.
- `HasJsonApi` is the trait that provides a default implementation of `toJsonApi()`, returning a `JsonApiResource` wrapping the model instance.

{% hint style="info" %}
You do not need to override `toJsonApi()` unless you want to customise the resource class used for this model. The default implementation provided by `HasJsonApi` is sufficient for the majority of use cases.
{% endhint %}

## Custom resource types

By default, the package derives the JSON:API type from the snake_case class basename (e.g. `BlogPost` → `blog_post`). To use a custom type string, register the model in the `resource_type_map` inside `config/apiable.php`:

```php
'resource_type_map' => [
    App\Models\Post::class => 'post',
    App\Models\User::class => 'user',
],
```

See the [Configuration](configuration.md) page for the full reference on `resource_type_map` and all other available options.

## Basic transformation in controllers

Once your models implement `JsonApiable`, you can serialize them directly using `JsonApiCollection` (for collections and paginators) or `JsonApiResource` (for single model instances).

{% tabs %}
{% tab title="Collection" %}
```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;

class PostController extends Controller
{
    public function index()
    {
        return new JsonApiCollection(Post::all());
    }
}
```
{% endtab %}
{% tab title="Single resource" %}
```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;

class PostController extends Controller
{
    public function show(Post $post)
    {
        return new JsonApiResource($post);
    }
}
```
{% endtab %}
{% tab title="Paginated collection" %}
```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;

class PostController extends Controller
{
    public function index()
    {
        return new JsonApiCollection(Post::paginate());
    }
}
```
{% endtab %}
{% endtabs %}

{% hint style="info" %}
`JsonApiCollection` accepts an `Illuminate\Support\Collection`, any Laravel paginator (`LengthAwarePaginator`, `SimplePaginator`, `CursorPaginator`), or any `Arrayable`. When a paginator is passed, pagination links and meta are automatically included in the JSON:API response.
{% endhint %}

## Full query building with filters, sorts, and includes

The basic resource classes shown above only handle serialization. For API endpoints that need to support client-driven filtering, sorting, sparse fieldsets, includes, and pagination, use `JsonApiResponse` instead.

See the [JsonApiResponse](../responses/json-api-response.md) page for the full guide.

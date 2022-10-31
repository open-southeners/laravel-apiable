---
layout: default
title: API
---

# API

::: danger
This page still work in progress as we're still on pre-releases.

**We absolutely DON'T recommend using this on production environments yet**.
:::

[[toc]]

## Illuminate\Database\Eloquent\Builder

::: tip
Please, note the **difference between Collection and Builder coming from an Eloquent model**, because that conditions the accesibility of these and other methods.
:::

Extending the framework `Illuminate\Database\Eloquent\Builder`.

**Source: [OpenSoutheners\LaravelApiable\Builder](https://github.com/open-southeners/laravel-apiable/blob/7caaa1dbf4925c53ff181630eec46d9b7df2c277/src/Builder.php)**

### jsonApiPaginate

Transforms collection of query results of valid `JsonApiable` resources to a paginated JSON:API collection (`JsonApiCollection`).

**Parameters:**

| Name     | Default |
| -------- | ------- |
| pageSize | `null`  |
| columns  | `['*']` |
| page     | `null`  |

**Example:**

```php
App\Models\Post::where('title', 'my filter')->jsonApiPaginate();
```

## Illuminate\Support\Collection

Extending the framework `Illuminate\Support\Collection`.

**Source: [OpenSoutheners\LaravelApiable\Collection](https://github.com/open-southeners/laravel-apiable/blob/7caaa1dbf4925c53ff181630eec46d9b7df2c277/src/Collection.php)**

### toJsonApi

Transforms collection of valid `JsonApiable` resources to a JSON:API collection (`JsonApiCollection`).

**Note: This method doesn't paginate, for pagination take a look to the Builder::jsonApiPaginate.**

**Parameters:**

_None..._

**Example:**

```php
App\Models\Post::where('title', 'my filter')->get()->toJsonApi();

// or

collect([Post::first(), Post::latest()->first()])->toJsonApi();
```

## OpenSoutheners\LaravelApiable\Contracts\JsonApiable

Model contract.

### toJsonApi

If the model below implements `OpenSoutheners\LaravelApiable\Contracts\JsonApiable` and uses the trait `OpenSoutheners\LaravelApiable\Concerns\HasJsonApi`, you could do the following to transform the model to JSON:API valid response:

```php
$post = App\Models\Post::first();

$post->toJsonApi();
```

## OpenSoutheners\LaravelApiable\Support\Apiable

These methods are available as global helpers functions (see examples).

### config

Method used to get user config parameters for this specific package.

**Example:**

<CodeGroup>
  <CodeGroupItem title="FACADE">

```php
Apiable::config('filters.default_operator', 'default value here');
```

  </CodeGroupItem>

  <CodeGroupItem title="HELPER">

```php
apiable()->config('filters.default_operator', 'default value here');
```

  </CodeGroupItem>
</CodeGroup>

### toJsonApi

Transform passed value (can be instance of different types: Builder, Model, Collection, etc...).

**Example:**

<CodeGroup>
  <CodeGroupItem title="FACADE">

```php
$post = Post::first();

Apiable::toJsonApi($post);

// or

$posts = Post::get();

Apiable::toJsonApi($posts);
```

  </CodeGroupItem>

  <CodeGroupItem title="HELPER">

```php
$post = Post::first();

apiable()->toJsonApi($post);

// or

$posts = Post::get();

apiable()->toJsonApi($post);
```

  </CodeGroupItem>
</CodeGroup>

### resourceTypeForModel

Guess resource type from model class or instance.

**Example:**

<CodeGroup>
  <CodeGroupItem title="FACADE">

```php
$post = Post::first();

Apiable::resourceTypeForModel($post);

// or

Apiable::resourceTypeForModel(Post::class);
```

  </CodeGroupItem>

  <CodeGroupItem title="HELPER">

```php
$post = Post::first();

apiable()->resourceTypeForModel($post);

// or

apiable()->resourceTypeForModel(Post::class);
```

  </CodeGroupItem>
</CodeGroup>

### getResourceType

Get resource type from model class or instance (if one specified, otherwise guess it using `resourceTypeForModel` method).

**Example:**

<CodeGroup>
  <CodeGroupItem title="FACADE">

```php
$post = Post::first();

Apiable::getResourceType($post);

// or

Apiable::getResourceType(Post::class);
```

  </CodeGroupItem>

  <CodeGroupItem title="HELPER">

```php
$post = Post::first();

apiable()->getResourceType($post);

// or

apiable()->getResourceType(Post::class);
```

  </CodeGroupItem>
</CodeGroup>

### jsonApiRenderable

Render errors in a JSON:API way. **[Check documentation on how to integrate this in your project.](README.md/#error-handling)**

**Example:**

<CodeGroup>
  <CodeGroupItem title="FACADE">

```php
try {
  // Code that might fails here...
} catch (\Throwable $e) {
  Apiable::jsonApiRenderable($e, request());
}
```

  </CodeGroupItem>

  <CodeGroupItem title="HELPER">

```php
try {
  // Code that might fails here...
} catch (\Throwable $e) {
  apiable()->jsonApiRenderable($e, request());
}
```

  </CodeGroupItem>
</CodeGroup>

### response

Render content as a JSON:API serialised response. **[Check documentation on how to customise these reponses.](responses.md)**

**Example:**

<CodeGroup>
  <CodeGroupItem title="FACADE">

```php
Apiable::response(Film::all())->allowing([
  // list of allowed user request params...
])->list();

// or

Apiable::response(Film::all(), [
  // list of allowed user request params...
]);
```

  </CodeGroupItem>

  <CodeGroupItem title="HELPER">

```php
apiable()->response(Film::all())->allowing([
  // list of allowed user request params...
])->list();

// or

apiable()->response(Film::all(), [
  // list of allowed user request params...
]);
```

  </CodeGroupItem>
</CodeGroup>


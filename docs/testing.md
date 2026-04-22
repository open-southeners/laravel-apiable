---
description: Assert JSON:API responses in PHPUnit with a fluent, chainable API.
---

# PHPUnit asserts

Laravel Apiable ships with testing utilities built on top of PHPUnit and Laravel's test response helpers. They give you a fluent, chainable API for asserting the shape and content of your JSON:API responses.

## Setup

No extra configuration is required. The `assertJsonApi` macro is registered on `Illuminate\Testing\TestResponse` automatically when the package service provider boots.

## assertJsonApi

The entry point for all JSON:API assertions. Call it on any `TestResponse` instance.

Without a callback it validates that the response is a structurally valid JSON:API document (must contain at least one of `data`, `errors`, or `meta`) and returns the response so you can continue chaining standard Laravel assertions.

```php
$response = $this->getJson('/posts');

$response->assertJsonApi();
```

Pass a closure to receive an `AssertableJsonApi` instance and make further assertions:

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasType('post')->hasId(1);
});
```

The `AssertableJsonApi` class extends Laravel's `AssertableJson`, so every parent method — `where`, `has`, `missing`, `count`, `first`, `each`, `etc`, `dd`, `dump`, `when`, `tap` — is fully available inside any scope.

---

## Response type assertions

### isResource

Assert that the response contains a single resource (not a collection).

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->isResource();
});
```

### isCollection

Assert that the response contains a collection (list of resources).

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->isCollection();
});
```

---

## Collection navigation

### at

Scope into the resource at the given zero-based position in the collection. Returns a new `AssertableJsonApi` scoped to that item, so you can chain further assertions against it.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->at(0)->hasAttribute('title', 'Hello world');
    $assert->at(1)->hasType('post');
});
```

Pass an optional closure as a second argument to run assertions inside that scope and have the method return the collection instance for continued chaining:

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->at(0, function (AssertableJsonApi $item) {
        $item->hasAttribute('title', 'Hello world')->etc();
    })->hasSize(3);
});
```

> **Note:** When using the closure form, call `etc()` at the end of the closure if you do not want PHPUnit to fail for properties you have not explicitly asserted. This is the standard `AssertableJson` behaviour.

### hasSize

Assert the number of resources present in the collection.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasSize(5);
});
```

---

## Identification assertions

### hasId

Assert that the current resource has the given ID. The value is cast to string internally, so passing an integer or string both work.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasId(1);
});
```

### hasType

Assert that the current resource has the given JSON:API type string.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasType('post');
});
```

---

## Attribute assertions

### hasAttribute

Assert that the resource has the specified attribute key. Pass a second argument to also assert the exact value using a strict (`===`) comparison.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasAttribute('title');
    $assert->hasAttribute('title', 'Hello world');
});
```

> **Breaking change from v4.2:** The value comparison is now strict (`assertSame`). Previously the check used `assertContains` on the attributes array, which could pass even when the value matched a *different* attribute key.

### hasNotAttribute

Assert that the resource does not have the specified attribute key. When a second argument is provided, the assertion passes if the key is absent *or* the key exists with a different value.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasNotAttribute('secret');
    $assert->hasNotAttribute('title', 'Forbidden title');
});
```

### hasAttributes

Assert multiple attributes at once. Accepts a **map** of name → value pairs (asserts key existence and exact value) or a **list** of names (asserts key existence only).

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    // map form: assert key + exact value
    $assert->hasAttributes([
        'title' => 'Hello world',
        'slug'  => 'hello-world',
    ]);

    // list form: assert key existence only
    $assert->hasAttributes(['title', 'slug', 'body']);
});
```

### hasNotAttributes

Assert that multiple attributes are absent (or do not hold the given values).

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasNotAttributes([
        'title' => 'Forbidden title',
        'slug'  => 'forbidden-slug',
    ]);
});
```

---

## Document scoping

These methods scope the fluent assertion context into a specific JSON:API document member. Inside the callback you have the full `AssertableJson` API — `where`, `has`, `missing`, `count`, `etc`, and so on. Call `etc()` at the end of a callback whenever you only check a subset of the properties in that scope.

### data

Scope into the top-level `data` member.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->data(function (AssertableJsonApi $data) {
        $data->where('type', 'post')
             ->where('id', '1')
             ->has('attributes')
             ->etc();
    });
});
```

### meta

Scope into the top-level `meta` member.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->meta(function (AssertableJsonApi $meta) {
        $meta->where('current_page', 1)
             ->where('total', 42)
             ->etc();
    });
});
```

### links

Scope into the top-level `links` member.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->links(function (AssertableJsonApi $links) {
        $links->has('next')->etc();
    });
});
```

### errors

Scope into the top-level `errors` member. Useful for asserting validation or business error responses. An `errors` document must not contain a `data` member — `assertJsonApi` enforces this at parse time.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->errors(function (AssertableJsonApi $errors) {
        $errors->count(2)
               ->where('0.status', '422')
               ->where('0.title', 'Invalid input')
               ->etc();
    });
});
```

### included

Scope into the top-level `included` array.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->included(function (AssertableJsonApi $included) {
        $included->count(2)->etc();
    });
});
```

### relationship

Scope into the `data` of a named relationship on the current resource (i.e. `relationships.{name}.data`).

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->relationship('author', function (AssertableJsonApi $author) {
        $author->where('type', 'client')->has('id');
    });
});
```

---

## Relationship assertions

### atRelation

Navigate to an included resource by its model instance and return a new `AssertableJsonApi` scoped to it. The model must be present in the response's `included` array.

```php
$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
    $assert->at(0)
        ->atRelation($relatedComment)
        ->hasAttribute('content', 'Foo bar');
});
```

Pass an optional closure to run assertions inside the scope and return `$this` instead:

```php
$assert->at(0)->atRelation($relatedComment, function (AssertableJsonApi $rel) {
    $rel->hasAttribute('content', 'Foo bar')->etc();
});
```

### hasAnyRelationships

Assert that the resource has at least one relationship of the given resource type. Pass a model class string or instance — the type is resolved automatically.

Set the second argument to `true` to also assert that the related resources appear in the `included` top-level key.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->at(0)->hasAnyRelationships('comment', true);
});
```

### hasNotAnyRelationships

Assert that the resource has no relationships of the given resource type. Set the second argument to `true` to also assert that no resources of that type appear in `included`.

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->at(0)->hasNotAnyRelationships('comment', true);
});
```

### hasRelationshipWith

Assert that a specific model instance is linked as a relationship of the current resource. Set the second argument to `true` to also verify the model appears in `included`.

```php
$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
    $assert->hasRelationshipWith($relatedComment, true);
});
```

### hasNotRelationshipWith

Assert that a specific model instance is not linked as a relationship of the current resource. Set the second argument to `true` to also verify the model is absent from `included`.

```php
$unrelatedComment = Comment::find(99);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($unrelatedComment) {
    $assert->hasNotRelationshipWith($unrelatedComment, true);
});
```

---

## Using parent AssertableJson methods

Because `AssertableJsonApi` fully extends `AssertableJson`, you can use any of its methods inside the document scoping callbacks:

| Method | Description |
|---|---|
| `where($key, $value)` | Assert an exact value at a dot-path |
| `whereNot($key, $value)` | Assert a value is not equal |
| `whereContains($key, $value)` | Assert a value is contained |
| `has($key)` | Assert a key exists |
| `missing($key)` | Assert a key does not exist |
| `count($key, $n)` | Assert the array at key has n items |
| `etc()` | Allow unchecked properties in the current scope |
| `dd()` / `dump()` | Dump the current scope for debugging |
| `when($condition, $callback)` | Conditional assertions |
| `tap($callback)` | Tap into the chain without changing it |

```php
$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->data(function (AssertableJsonApi $data) {
        $data->where('type', 'post')
             ->has('attributes')
             ->missing('password')
             ->etc();
    });

    $assert->meta(fn ($meta) => $meta->where('total', 10)->etc());
});
```

---

## Migrating from v4.2

### `hasAttribute` value comparison

The old implementation used `assertContains($value, $attributes)`, which matched the value against *any* attribute regardless of key. The new implementation uses `assertSame($value, $attributes[$name])`.

```php
// Before (could pass even if 'Hello' was under a different key)
$assert->hasAttribute('subtitle', 'Hello');

// After (checks the exact key)
$assert->hasAttribute('subtitle', 'Hello'); // fails if attributes['subtitle'] !== 'Hello'
```

### Collection-root attribute and relationship assertions

Before v4.3, `fromTestResponse` silently scoped a collection response to the first item, so `hasAttribute`, `hasId`, `hasType`, and relationship methods worked directly on the collection root. This behaviour is removed.

```php
// Before (implicitly operated on the first item)
$assert->hasAttribute('title', 'Hello');
$assert->hasAnyRelationships('comment', true);

// After — use at() to scope into a specific item first
$assert->at(0)->hasAttribute('title', 'Hello');
$assert->at(0)->hasAnyRelationships('comment', true);
```

### toArray shape

`toArray()` now returns the full document props (delegating to the parent `AssertableJson::toArray()`) instead of only the current resource's attributes.

```php
// Before
$assert->toArray(); // ['title' => '...', 'slug' => '...']

// After
$assert->toArray(); // ['data' => ['id' => '...', 'type' => '...', 'attributes' => [...]], ...]
```

---

## Chaining assertions

All assertion methods return `$this` (or a new `AssertableJsonApi` instance for navigation methods), so you can chain them freely:

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$relatedTag = Tag::find(1);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedTag) {
    $assert->isCollection()
        ->hasSize(3)
        ->at(0)
            ->hasType('post')
            ->hasAttribute('title', 'Hello world')
            ->hasRelationshipWith($relatedTag, true);
});
```

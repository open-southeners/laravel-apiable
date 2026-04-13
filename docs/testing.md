---
description: Test your JSON:API responses with built-in assertion helpers for PHPUnit.
---

# Testing

Laravel Apiable ships with testing utilities built on top of PHPUnit and Laravel's test response helpers. They give you a fluent, chainable API for asserting the shape and content of your JSON:API responses.

## Setup

No extra configuration is required. The `assertJsonApi` macro is registered on `Illuminate\Testing\TestResponse` automatically when the package service provider boots.

## assertJsonApi

The entry point for all JSON:API assertions. Call it on any `TestResponse` instance. Without a callback it simply validates that the response contains a structurally valid JSON:API `data` payload and returns the response so you can continue chaining standard Laravel assertions.

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

The callback receives the first resource in the response's `data` array, whether the response is a single resource or a collection.

---

## Response type assertions

### isResource

Assert that the response contains a single resource (not a collection).

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->isResource();
});
```

### isCollection

Assert that the response contains a collection (list of resources).

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->isCollection();
});
```

---

## Collection navigation

### at

Get the resource at a zero-based position in the collection. Returns a new `AssertableJsonApi` scoped to that item, so you can chain further assertions against it.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->at(0)->hasAttribute('title', 'Hello world');
    $assert->at(1)->hasType('post');
});
```

### hasSize

Assert the number of resources present in the collection.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasSize(5);
});
```

---

## Identification assertions

### hasId

Assert that the current resource has the given ID. The value is cast to string internally, so passing an integer or string both work.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasId(1);
});
```

### hasType

Assert that the current resource has the given JSON:API type string.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasType('post');
});
```

---

## Attribute assertions

### hasAttribute

Assert that the resource has the specified attribute key. Pass a second argument to also assert the value.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasAttribute('title');
    $assert->hasAttribute('title', 'Hello world');
});
```

### hasNotAttribute

Assert that the resource does not have the specified attribute key. Pass a second argument to also assert the value is absent.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasNotAttribute('secret');
    $assert->hasNotAttribute('title', 'Forbidden title');
});
```

### hasAttributes

Assert multiple attributes at once. Keys are attribute names, values are the expected values.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasAttributes([
        'title' => 'Hello world',
        'slug'  => 'hello-world',
    ]);
});
```

### hasNotAttributes

Assert that multiple attributes are absent (or do not have the given values).

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasNotAttributes([
        'title' => 'Forbidden title',
        'slug'  => 'forbidden-slug',
    ]);
});
```

---

## Relationship assertions

### atRelation

Navigate to an included resource by its model instance and return a new `AssertableJsonApi` scoped to it. The model must be present in the response's `included` array.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts?include=comments');

$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
    $assert->at(0)
        ->atRelation($relatedComment)
        ->hasAttribute('content', 'Foo bar');
});
```

### hasAnyRelationships

Assert that the resource has at least one relationship of the given resource type. Pass a model class string or instance as the first argument — the type is resolved automatically.

Set the second argument to `true` to also assert that the related resources appear in the `included` top-level key.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1?include=comments');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    // Assert the relationship exists (and is included)
    $assert->hasAnyRelationships('comment', true);
});
```

### hasNotAnyRelationships

Assert that the resource has no relationships of the given resource type. Set the second argument to `true` to also assert that no resources of that type appear in `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/2');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
    $assert->hasNotAnyRelationships('comment', true);
});
```

### hasRelationshipWith

Assert that a specific model instance is linked as a relationship of the current resource. Set the second argument to `true` to also verify the model appears in `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1?include=comments');

$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
    $assert->hasRelationshipWith($relatedComment, true);
});
```

### hasNotRelationshipWith

Assert that a specific model instance is not linked as a relationship of the current resource. Set the second argument to `true` to also verify the model is absent from `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$unrelatedComment = Comment::find(99);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($unrelatedComment) {
    $assert->hasNotRelationshipWith($unrelatedComment, true);
});
```

---

## Chaining assertions

All assertion methods return `$this` (or a new `AssertableJsonApi` instance in the case of navigation methods), so you can chain them freely:

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts?include=tags');

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

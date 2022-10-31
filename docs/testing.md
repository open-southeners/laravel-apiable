---
layout: default
title: Testing
category: Digging deeper
---

# Testing

This package also have some testing utilities built on top of PHPUnit and Laravel's framework assertions.

## Assertions

Simple assert that your API route is returning a proper JSON:API response:

```php
$response = $this->getJson('/posts');

$response->assertJsonApi();
```

[[toc]]

### at

Assert the resource at position of the collection starting by 0.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->at(0)->hasAttribute('title', 'Hello world');
});
```

### atRelation

Assert the related model.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts');

$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
  $assert->at(0)->atRelation($relatedComment)->hasAttribute('content', 'Foo bar');
});
```

### hasAttribute

Assert the resource has the specified attribute key and value.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasAttribute('title', 'Hello world');
});
```

### hasNotAttribute <Badge type="tip" text="1.1.0" vertical="middle" />

Assert the resource does not has the specified attribute key and value.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasNotAttribute('title', 'Hello world');
});
```

### hasAttributes

Assert the resource has the specified attributes keys and values.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasAttributes([
    'title' => 'Hello world'
    'slug' => 'hello-world'
  ]);
});
```

### hasNotAttributes <Badge type="tip" text="1.1.0" vertical="middle" />

Assert the resource does not has the specified attributes keys and values.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasNotAttributes([
    'title' => 'Hello world'
    'slug' => 'hello-world'
  ]);
});
```

### hasId

Assert the resource has the specified ID (or model key).

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasId(1);
});
```

### hasType

Assert the resource has the specified type.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasType('post');
});
```

### hasAnyRelationships

Assert that the resource **has any** relationships with the specified **resource type**.

Second parameter is for assert that the response **includes** the relationship data at the `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasAnyRelationships('comment', true);
});
```

### hasNotAnyRelationships

Assert that the resource **doesn't have any** relationships with the specified **resource type**.

Second parameter is for assert that the response **doesn't includes** the relationship data at the `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/2');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->hasNotAnyRelationships('comment', true);
});
```

### hasRelationshipWith

Assert that the specific model resource **is a** relationship with the parent resource.

Second parameter is for assert that the response **includes** the relationship data at the `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
  $assert->hasRelationshipWith($relatedComment, true);
});
```

### hasNotRelationshipWith

Assert that the specific model resource **is not** a relationship with the parent resource.

Second parameter is for assert that the response **doesn't includes** the relationship data at the `included`.

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$relatedComment = Comment::find(4);

$response->assertJsonApi(function (AssertableJsonApi $assert) use ($relatedComment) {
  $assert->hasRelationshipWith($relatedComment, true);
});
```

### isCollection

Assert that the response is a collection (list of resources).

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->isCollection();
});
```

### isResource

```php
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;

$response = $this->getJson('/posts/1');

$response->assertJsonApi(function (AssertableJsonApi $assert) {
  $assert->isResource();
});
```
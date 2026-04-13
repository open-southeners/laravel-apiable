---
description: Install and configure laravel-apiable in your Laravel application.
---

# Installation

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or higher

## Installing the package

Install the package via Composer:

```bash
composer require open-southeners/laravel-apiable
```

The package service provider is automatically discovered by Laravel, so no additional registration is required.

## Publishing the configuration

Publish the package configuration file to `config/apiable.php`:

```bash
php artisan vendor:publish --provider="OpenSoutheners\LaravelApiable\ServiceProvider"
```

## Setting up the resource type map

The `resource_type_map` in `config/apiable.php` is how the package knows which JSON:API type string to use for each Eloquent model. Open the published config and add your models:

```php
'resource_type_map' => [
    App\Models\Post::class   => 'post',
    App\Models\User::class   => 'user',
    App\Models\Tag::class    => 'tag',
],
```

{% hint style="info" %}
This mapping is conceptually similar to Laravel's [`Relation::enforceMorphMap()`](https://laravel.com/docs/master/eloquent-relationships#custom-polymorphic-types), but reversed: here the model class is the key and the type string is the value, whereas `enforceMorphMap` uses the type string as the key.
{% endhint %}

If a model is not listed in the map, the package falls back to a snake_case version of the class basename (e.g. `BlogPost` becomes `blog_post`).

### Programmatic alternative

Instead of (or in addition to) the config file, you can register the map at runtime using the `Apiable::modelResourceTypeMap()` facade method. This is useful for packages or when you want to keep the mapping close to your model definitions:

{% tabs %}
{% tab title="Associative (explicit types)" %}
```php
use OpenSoutheners\LaravelApiable\Support\Apiable;

// In a service provider boot() method
Apiable::modelResourceTypeMap([
    App\Models\Post::class => 'post',
    App\Models\User::class => 'user',
    App\Models\Tag::class  => 'tag',
]);
```
{% endtab %}
{% tab title="Non-associative (auto-derived types)" %}
```php
use OpenSoutheners\LaravelApiable\Support\Apiable;

// Types are derived automatically from the class basename (snake_case)
Apiable::modelResourceTypeMap([
    App\Models\Post::class,
    App\Models\User::class,
    App\Models\Tag::class,
]);
```
{% endtab %}
{% endtabs %}

{% hint style="warning" %}
Calling `Apiable::modelResourceTypeMap()` replaces the entire in-memory map. If you call it multiple times, only the last call's entries will be active. Merge your entries into a single call, or rely on the config file for the baseline and use the method for additions in the same boot cycle.
{% endhint %}

## Next steps

Once installation is complete and the resource type map is configured, proceed to [Model Setup](model-setup.md) to make your Eloquent models JSON:API-serializable.

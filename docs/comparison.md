---
layout: default
title: Comparison
category: Digging deeper
---

# Comparison

Of course there are lot more libraries out there for Laravel and even more generic (for PHP) that we can also use instead of this one.

So here we'll explain the differences between them and this one.

## skore-labs/laravel-json-api

**[Link to repository](https://github.com/laravel-json-api/laravel)**

Its our own! But now in a different organisation + renewed with more stuff on top of it (like a built-in query builder, JSON:API error handling, etc).

We recommend you to update to this one if you feel ready to jump to an almost similar experience, **but keep in mind this one requires Laravel 9+ and PHP 8.0+**.

## laravel-json-api/laravel

**[Link to repository](https://github.com/laravel-json-api/laravel)**

This is very similar to this new Laravel Apiable. Only problem thought is this package seems to achieve the same by an odd way and requires to add more "layers" on top of the ones that Laravel's already provides (API resources, etc). 

Also it comes licensed as Apache 2.0, while our is reusing the same license as Laravel does: MIT.

## spatie/laravel-query-builder

**[Link to repository](https://github.com/spatie/laravel-query-builder)**

Disadvantages compared to this:

- Doesn't integrate well with [GeneaLabs/laravel-model-caching](https://github.com/GeneaLabs/laravel-model-caching).
- Doesn't provide filter methods out-of-the-box for scopes (you need to create all by your own).
- Doesn't provide ability to use appended attributes in filters, sorts & appends (Laravel accessors).
- Doesn't do JSON:API response formatting.

## Fractal

**[Link to repository](https://github.com/thephpleague/fractal)**

Much simpler than the one above, but still adds a new layer (as **it is not a Laravel package**).

So it's much of the same, doesn't take advantage of the tools that the framework already provides like the API resources. Still a very good option thought as its one of the official _The PHP League_ packages, so you'll expect a very good support.
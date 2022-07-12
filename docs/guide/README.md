# Introduction

Install with the following command:

<CodeGroup>
  <CodeGroupItem title="COMPOSER">

```bash:no-line-numbers
composer require open-southeners/laravel-apiable
```

  </CodeGroupItem>
</CodeGroup>

## Getting started

First publish the config file once installed like this:

```sh
php artisan vendor:publish --provider="OpenSoutheners\LaravelApiable\ServiceProvider"
```

### Setup your models

This is a bit of manual work, but you need to setup your models in order for them to be JSON:API serializable entities:

```php

```

### Basic transformation usage

And, finally, use as simple as importing the class `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection` for collections or `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource` for resources.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use App\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new JsonApiCollection(
            User::all()
        );
    }
}
```

### Error handling

When your application returns errors and your frontend only understand JSON:API, then these needs to be transform. So we've you cover, set them up by simply doing the following on your `app/Exceptions/Handler.php`

```php
/**
 * Register the exception handling callbacks for the application.
 *
 * @return void
 */
public function register()
{
    $this->renderable(function (Throwable $e, $request) {
        if ($request->is('api/*') && app()->bound('apiable')) {
            return apiable()->jsonApiRenderable($e, $request);
        }
    });

    // Rest of the register method...
}
```

For further advance (or even more simple methods), you should [check out Responses section](responses.md).
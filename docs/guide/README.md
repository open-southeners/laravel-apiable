# Introduction

Install with the following command:

```sh
composer require open-southeners/laravel-apiable
```

## Getting started

First publish the config file once installed like this:

```sh
php artisan vendor:publish --provider="OpenSoutheners\LaravelApiable\ServiceProvider"
```

And use as simple as importing the class `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection` for collections or `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource` for resources.

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
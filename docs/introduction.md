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

```bash:no-line-numbers
php artisan vendor:publish --provider="OpenSoutheners\LaravelApiable\ServiceProvider"
```

Then edit the `resource_type_map` part including all your models like this:

```php
/**
 * Resource type model map.
 *
 * @see https://docs.opensoutheners.com/laravel-apiable/guide/#getting-started
 */
'resource_type_map' => [
  App\Models\Film::class => 'film',
  App\Models\Review::class => 'review',
],
```

**If you see, this is same as Laravel's [`Relation::enforceMorphMap()`](https://laravel.com/docs/master/eloquent-relationships#custom-polymorphic-types) but reversed.**

### Setup your models

::: tip
For more information about how to customise this [check out Responses section](responses.md).
:::

This is a bit of manual work, but you need to setup your models in order for them to be JSON:API serializable entities:

```php
use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Concerns\HasJsonApi;

class Film extends Model implements JsonApiable
{
    use HasJsonApi;

    // rest of your model
}
```

You need to add that `implements JsonApiable` to your class importing this class and the `jsonApiableOptions` method.

### Basic transformation usage

And, finally, use as simple as importing the class `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection` for collections or `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource` for resources.

```php
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use App\Models\Film;

class FilmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection<\App\Models\Film>
     */
    public function index()
    {
        return new JsonApiCollection(Film::all());
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

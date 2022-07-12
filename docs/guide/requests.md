
# Requests <Badge type="tip" text="new" vertical="middle" />

As a new addition to this package, you can now allow your users to use:

- Filters by attributes or local query scopes.
- Include model relationships.
- Append model accessors ([learn more about them](https://laravel.com/docs/master/eloquent-serialization#appending-values-to-json)).
- Select fields from the database (sparse fieldset).
- Sort by attributes.

All of the following being conditionally allowed by you on your controllers, like the following example:

```php
/**
 * Display a listing of the resource.
 *
 * @return \OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection<\App\Models\Post>
 */
public function index()
{
    return JsonApiResponse::from(Post::class)
        ->allowing([
            AllowedFilter::similar('title'),
            AllowedFilter::similar('film.title'),
            AllowedFilter::similar('content'),
            AllowedSort::make('created_at'),
            AllowedSort::make('user_id'),
            AllowedInclude::make('author'),
        ])->list();
}
```

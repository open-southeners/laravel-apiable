---
description: >-
  Allow requests through your application to send specific parameters like
  filters, includes, appends, fields, sorts and search.
---

# Requests

As a new addition to this package, you can now allow your users to use:

* Filters by attributes or local query scopes.
* Include model relationships.
* Append model accessors ([learn more about them](https://laravel.com/docs/master/eloquent-serialization#appending-values-to-json)).
* Select fields from the database ([sparse fieldset](https://jsonapi.org/format/#fetching-sparse-fieldsets)).
* Sort by attributes.
* Perform a full-text search (using [Laravel Scout](https://laravel.com/docs/master/scout)).

{% hint style="info" %}
Remember that there are 2 ways to achieve the exact same behaviour on this package, you can use [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) or normal methods. Advantage of these is that **attributes can be at the method or class level.**
{% endhint %}

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
        ]);
}
```

## Allow includes

You can allow users to include relationships to the JSON:API response.

{% tabs %}
{% tab title="Using methods" %}
```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedInclude::make('author'),
  AllowedInclude::make('reviews'),
]);
```

You may also allow nested includes like so:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedInclude::make('author'),
  AllowedInclude::make('author.reviews'),
]);
```

Or using a more specific method like `allowIncludes`:

```php
JsonApiResponse::from(Film::class)->allowInclude('author');

// or

JsonApiResponse::from(Film::class)
    ->allowInclude(AllowedInclude::make('author'));
```
{% endtab %}

{% tab title="Using attributes" %}
```php
#[IncludeQueryParam('author')]
#[IncludeQueryParam('author.reviews')]
public function index(JsonApiResponse $response)
{
    return $response->using(Post::class);
}
```
{% endtab %}
{% endtabs %}

## Allow sorts

You can allow your users to sort by descendant or ascendant order (or both, **which is the default behaviour**).

{% tabs %}
{% tab title="Using methods" %}
```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedSort::make('created_at'),
]);
```

As top uses the direction defined in the config as default you can specify a direction instead, the following are all doing the same but in different directions:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedSort::ascendant('created_at'),
]);

JsonApiResponse::from(Film::class)->allowSort(
  AllowedSort::descendant('created_at')
);

JsonApiResponse::from(Film::class)
  ->allowSort('created_at', AllowedSort::DESCENDANT);

JsonApiResponse::from(Film::class)
  ->allowSort('created_at', SortDirection::ASCENDANT->value);
```

Default sorts can be applied by using the following method:

```php
JsonApiResponse::from(Film::class)
    ->applyDefaultSort('created_at'); // ascendant by default

JsonApiResponse::from(Film::class)
    ->applyDefaultSort('created_at', DefaultSort::ASCENDANT);

JsonApiResponse::from(Film::class)
    ->applyDefaultSort('created_at', DefaultSort::DESCENDANT);
```
{% endtab %}

{% tab title="Using attributes" %}
<pre class="language-php"><code class="lang-php">#[SortQueryParam('created_at')]
#[SortQueryParam('review_points', AllowedSort::ASCENDANT)]
public function index(JsonApiResponse $response)
{
<strong>    JsonApiResponse::from(Film::class);
</strong>}
</code></pre>

You can apply some sorts by default when no others are being sent by:

```php
#[ApplyDefaultSort('created_at', DefaultSort::DESCENDANT)]
public function index(JsonApiResponse $response)
{
    JsonApiResponse::from(Film::class);
}
```
{% endtab %}
{% endtabs %}

## Allow filters

{% hint style="info" %}
Remember that a `similar` filter is using `LIKE` comparison at the end (on the database), while an exact is using `MATCH` (or `=`). Use them depending of your case. **By default it uses `LIKE` comparison.**
{% endhint %}

You can allow your users to filter by a model attribute or its relation's attributes.

{% tabs %}
{% tab title="Using methods" %}
By default will use similar (LIKE) but you may change this in the `config/apiable.php` file:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::make('title'),
]);
```

You can also specify any method you like:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::similar('title'),
  AllowedFilter::exact('author.name'),
  AllowedFilter::greaterThan('review_points'),
  AllowedFilter::greaterOrEqualThan('review_points'),
  AllowedFilter::lowerThan('review_points'),
  AllowedFilter::lowerOrEqualThan('review_points'),
]);
```

Scoped filters might be used if you want to filter using Eloquent's query scopes:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::scoped('active'),
]);

// or

JsonApiResponse::from(Film::class)
  ->allowFilter('active', AllowedFilter::SCOPE);
```

And even restrict what they can use for filter on each filter like so:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::similar('title', ['2012', 'Jumaji']),
  AllowedFilter::exact('author.name', ['Rubén Robles', 'Taylor Otwell']),
]);
```

Same with sorts you can apply default filters whenever a user didn't send any via HTTP query parameters that were allowed:

```php
JsonApiResponse::from(Film::class)
    ->applyDefaultFilter('name', AllowedFilter::EXACT, '2012');

JsonApiResponse::from(Film::class)
    ->applyDefaultFilter('name', AllowedFilter::SIMILAR, 'The');
```
{% endtab %}

{% tab title="Using attributes" %}
```php
#[FilterQueryParam('title', AllowedFilter::SIMILAR)]
#[FilterQueryParam('author.name', AllowedFilter::EXACT, ['Rubén Robles', 'Taylor Otwell'])]
#[FilterQueryParam('review_points', AllowedFilter::GREATER_THAN)]
#[FilterQueryParam('review_points', AllowedFilter::GREATER_OR_EQUAL_THAN)]
#[FilterQueryParam('review_points', AllowedFilter::LOWER_THAN)]
#[FilterQueryParam('review_points', AllowedFilter::LOWER_OR_EQUAL_THAN)]
#[FilterQueryParam('active', AllowedFilter::SCOPE)]
public function index(JsonApiResponse $response)
{
    JsonApiResponse::from(Film::class);
}
```

You can apply default filters as well via attributes:

```php
#[FilterQueryParam('status', AllowedFilter::EXACT, FilmStatus::Published->value)]
public function index(JsonApiResponse $response)
{
    JsonApiResponse::from(Film::class);
}
```
{% endtab %}
{% endtabs %}

## Allow fields (sparse fieldset)

{% hint style="info" %}
This part just fully complaints with JSON:API, while the `allowAppends` doesn't as it's something specially adapted to Laravel.
{% endhint %}

Allow fields will limit the columns selected by the database query being ran by Laravel.

{% tabs %}
{% tab title="Using methods" %}
```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFields::make('film', ['title', 'shortDescription', 'description', 'created_by']),
  AllowedFields::make('user', ['name', 'email']),
]);

// or

JsonApiResponse::from(Film::class)
  ->allowFields('film', ['title', 'shortDescription', 'description', 'created_by'])
  ->allowFields('user', ['name', 'email']);
```

You can also use models in replace of that first argument (the resource type):

```php
JsonApiResponse::from(Film::class)
  ->allowFields(User::class, ['is_active']);
```

Or directly an array as first argument to append to the main response resource (in this case film):

```php
JsonApiResponse::from(Film::class)
  ->allowFields(['is_published']);
```
{% endtab %}

{% tab title="Using attributes" %}
```php
#[FieldsQueryParam('film', ['title', 'shortDescription', 'description', 'created_by'])]
#[FieldsQueryParam(User::class, ['name', 'email'])]
public function index(JsonApiResponse $response)
{
    JsonApiResponse::from(Film::class);
}
```
{% endtab %}
{% endtabs %}

## Allow appends

Same as allowing fields by resource type but this will append [Model accessors](broken-reference) after the query is done.

{% tabs %}
{% tab title="Using methods" %}
```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedAppends::make('user', ['is_active']),
]);

// or

JsonApiResponse::from(Film::class)
  ->allowAppends('user', ['is_active']);
```

You can also use models in replace of that first argument (the resource type):

```php
JsonApiResponse::from(Film::class)
  ->allowAppends(User::class, ['is_active']);
```

Or directly an array as first argument to append to the main response resource (in this case film):

```php
JsonApiResponse::from(Film::class)
  ->allowAppends(['is_published']);
```
{% endtab %}

{% tab title="Using attributes" %}
```php
#[AppendsQueryParam('film', ['is_published'])]
#[AppendsQueryParam(User::class, ['is_active'])]
public function index(JsonApiResponse $response)
{
    JsonApiResponse::from(Film::class);
}
```
{% endtab %}
{% endtabs %}

## Allow search

{% hint style="info" %}
This feature is only available for proper setup of [Laravel Scout](https://laravel.com/docs/master/scout#installation) in your model.
{% endhint %}

You can also perform full-text search with this package with the help of Laravel Scout. So the frontend should send something like `yourapi.com/?q=search_query` or `yourapi.com/?search=search_query`) to perform a search if allowed by the backend.

{% tabs %}
{% tab title="Using methods" %}
```php
JsonApiResponse::from(Film::class)->allowSearch();
```
{% endtab %}

{% tab title="Using attributes" %}
```php
#[SearchQueryParam()]
public function index(JsonApiResponse $response)
{
    JsonApiResponse::from(Film::class);
}
```
{% endtab %}
{% endtabs %}

## Include allowed filters & sorts on the response

If you have a table or a component in the frontend that needs to know about what can be filtered or sorted by, you may want to add this to your JSON:API response:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::similar('title'),
  AllowedSort::ascendant('created_at'),
])->includeAllowedToResponse();
```

Then the response payload will look like this:

```json
{
  "data": [
    {
      "id": "1",
      "type": "film",
      "attributes": {
        "title": "2012",
        "shortDescription": "Ratione omnis necessitatibus facere eius culpa molestiae necessitatibus non. Voluptatem saepe rerum...",
        "description": "Ratione omnis necessitatibus facere eius culpa molestiae necessitatibus non. Voluptatem saepe rerum quis aliquid eum odit natus eveniet.\n\nFugiat at reiciendis cum repellendus. Quibusdam dolor fuga ut repudiandae necessitatibus similique. Magnam adipisci et iure consequatur deleniti in.\n\nHic unde odit distinctio et hic assumenda. Est et inventore at praesentium. Sapiente autem animi architecto rem. Delectus sed ratione ad incidunt ut odio ut quaerat.",
        "created_at": "2020-07-08T17:14:54.000000Z",
        "updated_at": "2020-09-04T05:54:41.000000Z"
      }
    },
    {
      "id": "2",
      "type": "film",
      "attributes": {
        "title": "The Lost City",
        "shortDescription": "Velit eum nemo quibusdam quaerat illo magni dolorem similique. Assumenda quo laborum perspiciatis er...",
        "description": "Velit eum nemo quibusdam quaerat illo magni dolorem similique. Assumenda quo laborum perspiciatis error aut ut ut.\n\nVel ipsam sed ratione illo. Placeat quis aut qui tempore in. Est est laudantium explicabo consequatur officiis consequatur voluptas. Sit rerum modi veniam voluptatem unde officia mollitia.\n\nNesciunt consequatur architecto recusandae aut quis. Perspiciatis ut magnam quidem voluptatum. Optio eaque ad nesciunt ea dignissimos eos nemo. Aut sed beatae earum quas.",
        "created_at": "2021-07-21T22:23:39.000000Z",
        "updated_at": "2021-02-01T08:52:39.000000Z"
      }
    },
    {
      "id": "3",
      "type": "film",
      "attributes": {
        "title": "Ma",
        "shortDescription": "Aut illum dolores amet aliquam amet facilis aut. Ratione nesciunt qui est voluptate. Consequatur con...",
        "description": "Aut illum dolores amet aliquam amet facilis aut. Ratione nesciunt qui est voluptate. Consequatur consequatur adipisci quas sit. Ut non iste animi distinctio enim voluptatem.\n\nSuscipit adipisci nihil sit. Officiis accusamus ut itaque alias ipsum ullam qui. Sit quia in ea maiores ut error vel.\n\nEsse nesciunt similique libero fugit voluptas fugit. Necessitatibus est ut numquam molestiae nisi quis est. Eos et voluptatibus in alias exercitationem alias rem. Voluptate accusamus quia ut id.",
        "created_at": "2021-09-21T10:51:02.000000Z",
        "updated_at": "2021-09-25T17:42:59.000000Z"
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/films?page%5Bnumber%5D=1",
    "last": "http://localhost:8000/api/films?page%5Bnumber%5D=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/films?page%5Bnumber%5D=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "path": "http://localhost:8000/api/films",
    "per_page": 15,
    "to": 3,
    "total": 3,
    "allowed_filters": {
      "title": {
        "like": "*"
      }
    },
    "allowed_sorts": {
      "created_at": "asc"
    }
  }
}
```

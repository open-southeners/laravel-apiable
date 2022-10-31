---
layout: default
title: Requests
category: Introduction
---

# Requests

As a new addition to this package, you can now allow your users to use:

- Filters by attributes or local query scopes.
- Include model relationships.
- Append model accessors ([learn more about them](https://laravel.com/docs/master/eloquent-serialization#appending-values-to-json)).
- Select fields from the database ([sparse fieldset](https://jsonapi.org/format/#fetching-sparse-fieldsets)).
- Sort by attributes.
- Perform a fulltext search (using [Laravel Scout](https://laravel.com/docs/master/scout)).

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

## Allow includes

You can allow users to include relationships to the JSON:API response like so:

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

Or even use a "less-generic" method like `allowIncludes`:

```php
JsonApiResponse::from(Film::class)->allowInclude('author');

// or

JsonApiResponse::from(Film::class)->allowInclude(AllowedInclude::make('author'));
```

## Allow sorts

::: tip
Remember that you can use `OpenSoutheners\LaravelApiable\Http\SortDirection` enum, **only if you're using PHP 8.1+**.
:::

You can allow your users to sort by descendant or ascendant order (or both, **which is the default behaviour**):

<CodeGroup>
  <CodeGroupItem title="BOTH">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedSort::make('created_at'),
]);
```

  </CodeGroupItem>
  <CodeGroupItem title="ASCENDANT">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedSort::ascendant('created_at'),
]);

// or

JsonApiResponse::from(Film::class)->allowSort(AllowedSort::ascendant('created_at'));

// or

JsonApiResponse::from(Film::class)->allowSort('created_at', 'asc');

// or if you have PHP8.1+

JsonApiResponse::from(Film::class)->allowSort('created_at', SortDirection::ASCENDANT->value);
```

  </CodeGroupItem>
  <CodeGroupItem title="DESCENDANT">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedSort::descendant('created_at'),
]);

// or

JsonApiResponse::from(Film::class)->allowSort(AllowedSort::descendant('created_at'));

// or

JsonApiResponse::from(Film::class)->allowSort('created_at', 'desc');

// or if you have PHP8.1+

JsonApiResponse::from(Film::class)->allowSort('created_at', SortDirection::DESCENDANT->value);
```

  </CodeGroupItem>
</CodeGroup>

## Allow filters

::: tip
Remember that an `exact` filter is using `LIKE` comparison at the end (on the database), while an exact is using `MATCH` (or `=`). Use them depending of your case. **By default it uses `LIKE` comparison.**
:::

::: tip
Also filters automatically detects wether you have a scope or an attribute.
:::

You can allow your users to filter by a model attribute or its relation's attributes:

<CodeGroup>
  <CodeGroupItem title="DEFAULT">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::make('title'),
  AllowedFilter::make('author.name'),
]);
```

  </CodeGroupItem>
  <CodeGroupItem title="SIMILAR">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::similar('title'),
  AllowedFilter::similar('author.name'),
]);
```

  </CodeGroupItem>
  <CodeGroupItem title="EXACT">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::exact('title'),
  AllowedFilter::exact('author.name'),
]);
```

  </CodeGroupItem>
  
  <CodeGroupItem title="SCOPE">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::scoped('active'),
]);
```

  </CodeGroupItem>
</CodeGroup>

And even restrict what they can use for filter on each filter like so:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::similar('title', ['2012', 'Jumaji']),
  AllowedFilter::exact('author.name', ['Rubén Robles', 'Taylor Otwell']),
]);
```

## Allow fields (sparse fieldset)

::: tip
This part just fully complaints with JSON:API, while the `allowAppends` doesn't as it's something adapted to Laravel.
:::

You can allow fields by resource type like so:

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

## Allow appends

Same as allowing fields by resource type but this will append [Model accessors]() after the query is done:

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedAppends::make('user', ['is_active']),
]);

// or

JsonApiResponse::from(Film::class)->allowAppends('user', ['is_active']);
```

## Allow search <Badge type="tip" text="1.0.0" vertical="middle" />

::: danger
This feature is only available for proper setup of [Laravel Scout](https://laravel.com/docs/master/scout#installation) in your model.
:::

You can also allow fulltext search (by sending `yourapi.com/?q=search_query` or `yourapi.com/?search=search_query`) to users like this:

```php
JsonApiResponse::from(Film::class)->allowSearch();
```

## Include allowed filters & sorts on the response

If you have a table or a component in the frontend that needs to know about what can be filtered or sorted by, you may want to add this to your JSON:API response:

<CodeGroup>
  <CodeGroupItem title="BACKEND">

```php
JsonApiResponse::from(Film::class)->allowing([
  AllowedFilter::similar('title'),
  AllowedSort::ascendant('created_at'),
])->includeAllowedToResponse()->list();
```

  </CodeGroupItem>
  <CodeGroupItem title="RESPONSE">

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

  </CodeGroupItem>
</CodeGroup>
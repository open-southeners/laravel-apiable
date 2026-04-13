---
description: Validate incoming query parameters against your allowed definitions.
---

# Validation

By default, Laravel Apiable silently ignores query parameters that are not in the allowed list. Unrecognised filters are skipped, unknown sorts are dropped, and so on. This is intentional — it makes your API more permissive during development and avoids breaking clients that send extra parameters.

When you want stricter behaviour, you can enable parameter validation to reject any parameter that does not match your allowed definitions.

## Enabling validation

Set `requests.validate_params` to `true` in `config/apiable.php`:

```php
'requests' => [
    'validate_params' => true,
    // ...
],
```

With validation enabled, any query parameter that does not match an allowed definition causes the package to throw an exception, which results in an error response to the client.

## What is validated

The `QueryParamsValidator` class is used internally for every request feature. When `validate_params` is `true`, it enforces:

| Feature | Rejection condition |
|---|---|
| Filters | Attribute not in `allowedFilters`, operator not allowed for that attribute, or value not in the restricted set |
| Sorts | Attribute not in `allowedSorts`, or direction not permitted (e.g. sending `?sort=-title` when only `ASCENDANT` is configured) |
| Includes | Relationship not in `allowedIncludes` |
| Fields | Resource type not in `allowedFields`, or column not in the allowed list for that type |
| Appends | Resource type not in `allowedAppends`, or accessor not in the allowed list for that type |
| Search filters | Attribute not in `allowedSearchFilters`, or value not in the restricted set |

## Error response

When a parameter fails validation, the package throws a PHP `Exception` with a message describing the rejected parameter, for example:

```
"title" is not filterable or contains invalid values
"price" is not sortable
"comments" cannot be included
```

Your application's exception handler is responsible for converting this into an HTTP response. If you are using Laravel's default handler with JSON requests, it will return a `500` response by default. To return a proper `400 Bad Request` or `422 Unprocessable Content` response, catch these exceptions in your `bootstrap/app.php` exception handler:

```php
use Illuminate\Foundation\Configuration\Exceptions;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Exception $e, \Illuminate\Http\Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'Invalid query parameter',
                        'detail' => $e->getMessage(),
                    ],
                ],
            ], 400);
        }
    });
})
```

## `QueryParamsValidator` internals

The `QueryParamsValidator` class is used by the `AllowsFilters`, `AllowsSorts`, `AllowsIncludes`, `AllowsFields`, `AllowsAppends`, and `AllowsSearch` traits. Each trait passes its params and rules to the validator, which runs a chain of condition callbacks.

When `validate_params` is `false`, failed conditions are silently skipped and only matching parameters are returned. When `validate_params` is `true`, a failed condition calls the associated exception handler instead.

You can check at runtime whether validation is enforced:

```php
/** @var \OpenSoutheners\LaravelApiable\Http\RequestQueryObject $requestQueryObject */
$requestQueryObject->enforcesValidation(); // bool
```

{% hint style="info" %}
Validation is applied per-feature independently. You can have strict filter validation while sorts remain permissive if you configure your `allowedSorts` broadly. The config flag is global, but the rules you declare determine what passes.
{% endhint %}

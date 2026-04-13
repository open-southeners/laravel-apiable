---
description: Render application errors and exceptions in JSON:API format.
---

# Error Handling

Laravel Apiable provides a `Handler` class that converts any PHP exception into a properly formatted JSON:API error response. Validation errors, authentication failures, and HTTP exceptions are each handled appropriately with the correct status codes.

## Registering the error handler

### Laravel 11+ (bootstrap/app.php)

In Laravel 11 and later, register the renderable inside `bootstrap/app.php`:

```php
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') && app()->bound('apiable')) {
                return Apiable::jsonApiRenderable($e);
            }
        });
    })->create();
```

### Laravel 10 and below (app/Exceptions/Handler.php)

For older applications, add the renderable inside the `register()` method of your exception handler:

```php
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use Throwable;

public function register(): void
{
    $this->renderable(function (Throwable $e, $request) {
        if ($request->is('api/*') && app()->bound('apiable')) {
            return Apiable::jsonApiRenderable($e);
        }
    });
}
```

## `Apiable::jsonApiRenderable()`

```php
Apiable::jsonApiRenderable(Throwable $e, ?bool $withTrace = null): Handler
```

Creates a `Handler` instance that implements `Responsable`. The returned object can be returned directly from a renderable closure — Laravel will call `toResponse()` on it.

| Parameter | Type | Description |
|---|---|---|
| `$e` | `Throwable` | The exception to convert. |
| `$withTrace` | `bool\|null` | Include stack trace in the response. Defaults to `config('app.debug')`. Pass `true` or `false` to override. |

## How the Handler works

The `Handler` class inspects the exception type and chooses the appropriate behaviour:

### Validation exceptions

`Illuminate\Validation\ValidationException` is unpacked field-by-field. Each validation message becomes a separate error object with a `source.pointer` referencing the failed field:

```json
{
  "errors": [
    {
      "title": "The title field is required.",
      "source": { "pointer": "title" },
      "status": "422"
    },
    {
      "title": "The body must be at least 10 characters.",
      "source": { "pointer": "body" },
      "status": "422"
    }
  ]
}
```

### HTTP exceptions

Any exception implementing `Symfony\Component\HttpKernel\Exception\HttpExceptionInterface` (including Laravel's `abort()` helpers) uses the exception's own status code. Response headers from the exception are forwarded automatically.

### Authentication exceptions

`Illuminate\Auth\AuthenticationException` is mapped to HTTP `401 Unauthorized`, since Laravel's native exception does not implement `HttpExceptionInterface`.

### All other exceptions

Generic exceptions produce an HTTP `500 Internal Server Error`. When `app.debug` is `false` (or `$withTrace` is `false`), the message is replaced with the generic text `"Internal server error."` and the trace is omitted. In debug mode the real message and stack trace are included.

`Illuminate\Database\QueryException` also includes the database error code in the `code` field when trace is enabled.

## JSON:API error response structure

```json
{
  "errors": [
    {
      "title": "Unauthenticated.",
      "status": "401"
    }
  ]
}
```

Each error object may contain:

| Field | Description |
|---|---|
| `title` | Short, human-readable summary of the error. |
| `detail` | Longer explanation (optional). |
| `source.pointer` | JSON pointer to the field that caused the error (validation only). |
| `status` | HTTP status code as a string. |
| `code` | Application-specific error code (query errors in debug mode). |
| `trace` | Stack trace array (only when debug mode is enabled). |

## Adding custom response headers

The `Handler` instance returned by `jsonApiRenderable()` exposes `withHeader()` for adding headers to the final response:

```php
$exceptions->renderable(function (Throwable $e, $request) {
    if ($request->is('api/*')) {
        return Apiable::jsonApiRenderable($e)
            ->withHeader('X-Error-Id', (string) Str::uuid());
    }
});
```

Headers from `HttpExceptionInterface` exceptions (such as `WWW-Authenticate` on a 401) are always merged in automatically.

## `JsonApiException`: stacking multiple errors

You can build a response with multiple error objects programmatically using `JsonApiException`:

```php
use OpenSoutheners\LaravelApiable\JsonApiException;

$exception = new JsonApiException();

$exception->addError(
    title: 'Invalid value for filter.',
    detail: 'The "status" filter only accepts: draft, published.',
    source: 'filter[status]',
    status: 422,
);

$exception->addError(
    title: 'Unknown sort field.',
    source: 'sort',
    status: 400,
);

throw $exception;
```

### `addError()` signature

```php
public function addError(
    string $title,
    ?string $detail = null,
    ?string $source = null,
    ?int $status = 500,
    int|string|null $code = null,
    array $trace = []
): void
```

### `getErrors()` / `toArray()`

```php
$exception->getErrors(); // returns the raw errors array
$exception->toArray();   // returns ['errors' => [...]]
```

{% hint style="info" %}
`JsonApiException` extends `Exception`, so it can be thrown anywhere in your application and caught by the registered renderable.
{% endhint %}

## Forcing trace output

Pass `true` as the second argument to always include the stack trace, regardless of `app.debug`:

```php
return Apiable::jsonApiRenderable($e, withTrace: true);
```

Pass `false` to always suppress it:

```php
return Apiable::jsonApiRenderable($e, withTrace: false);
```

{% hint style="warning" %}
Never expose stack traces in production. Only pass `withTrace: true` in controlled environments such as staging or local debugging.
{% endhint %}

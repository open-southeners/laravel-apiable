---
description: Control response formatting based on Accept headers or force a specific format for your API.
---

# Content Negotiation

`JsonApiResponse` inspects the request's `Accept` header to decide how to serialize a response. This makes it easy to serve JSON:API to API clients while also supporting standard JSON for tools like Inertia.js — without writing separate controllers.

## Supported formats

| `Accept` header | Behavior |
|---|---|
| `application/vnd.api+json` | Full JSON:API serialization via `Apiable::toJsonApi()` |
| `application/json` | Standard JSON, simple paginated when the query is a builder |
| `raw` | Same as `application/json` — intended for internal/Inertia use |
| _(anything else)_ | Throws `406 Not Acceptable` |

## Checking the Accept header

The package registers a `wantsJsonApi()` macro on `Illuminate\Http\Request`. Use it anywhere you need to branch on the requested format:

```php
if ($request->wantsJsonApi()) {
    // client sent Accept: application/vnd.api+json
}
```

## Forcing a format per response

### forceFormatting()

Call `forceFormatting()` on a `JsonApiResponse` instance to bypass Accept-header negotiation for that response. The format is temporarily set via `config()` for the duration of the request.

```php
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;

// Force JSON:API (uses the default from config)
JsonApiResponse::from(Film::class)->forceFormatting();

// Force standard JSON
JsonApiResponse::from(Film::class)->forceFormatting('application/json');
```

Passing `null` (or calling without arguments) forces formatting using whatever `responses.formatting.type` is set to in the config.

## Config-based defaults

Formatting behavior is controlled in `config/apiable.php`:

```php
'responses' => [
    'formatting' => [
        // Default format used when forcing or when no Accept header is present
        'type' => 'application/vnd.api+json',

        // When true, ignores Accept headers and always uses `type`
        'force' => false,
    ],
],
```

Setting `force` to `true` globally is equivalent to calling `forceFormatting()` on every response. This is useful when your application only needs one format and you want to avoid depending on clients sending correct headers.

{% hint style="warning" %}
Globally forcing formatting disables content negotiation for all responses. Clients that send an `Accept: application/json` header will still receive JSON:API format. Only use this when all consumers of the API agree on a single format.
{% endhint %}

## Inertia.js detection

When the request is detected as an Inertia request (`X-Inertia` header), `JsonApiResponse` automatically treats the format as `raw` and returns the paginated data object directly — bypassing the JSON:API envelope. The `forceFormatting()` setting does not affect this behavior.

## 406 Not Acceptable

If the `Accept` header contains an unsupported media type and formatting is not forced, the package throws an `HttpException` with status `406`:

```
HTTP/1.1 406 Not Acceptable
```

This follows the JSON:API specification which requires servers to respond with `406` when none of the client's acceptable types can be served.

{% hint style="info" %}
If you want to suppress the 406 and fall back to a default format, set `responses.formatting.force` to `true` and choose a `type` in the config.
{% endhint %}

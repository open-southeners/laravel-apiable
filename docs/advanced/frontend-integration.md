---
description: Integrate your frontend application with a JSON:API backend.
---

# Frontend Integration

Laravel Apiable produces standard JSON:API responses, which means any compliant client library can consume your API without any custom parsing logic. This page covers recommended client libraries and server-side helpers for working with JSON:API from JavaScript (browser and Node/SSR environments).

## JavaScript client libraries

### jsona

[jsona](https://github.com/olosegres/jsona) is a lightweight JavaScript library that deserializes JSON:API payloads into plain objects and re-serializes them back into the JSON:API format. It works in the browser and in Node.js / SSR environments (Next.js, Nuxt, etc.).

```bash
npm install jsona
```

```js
import Jsona from 'jsona'

const dataFormatter = new Jsona()

// Deserialize a JSON:API response
const posts = dataFormatter.deserialize(response.data)

// Serialize back to JSON:API for PATCH / POST requests
const payload = dataFormatter.serialize({ stuff: post, includeNames: ['tags'] })
```

For a full list of JSON:API client libraries for every language and framework, see the official registry at [jsonapi.org/implementations](https://jsonapi.org/implementations/).

---

## Building JSON:API URLs

### Flex URL

[Flex URL](https://github.com/open-southeners/flex-url) is an open-source package maintained by Open Southeners that provides a fluent builder for constructing and parsing URLs that follow the JSON:API query parameter conventions (`filter`, `sort`, `include`, `fields`, `page`). It runs in the browser and in Node.js.

- **Repository**: https://github.com/open-southeners/flex-url
- **Documentation**: https://docs.opensoutheners.com/flex-url/

```bash
npm install @open-southeners/flex-url
```

```js
import { url } from '@open-southeners/flex-url'

const apiUrl = url('https://api.example.com/posts')
  .filter('status', 'published')
  .sort('-created_at')
  .include('tags', 'author')
  .page(1)
  .toString()

// https://api.example.com/posts?filter[status]=published&sort=-created_at&include=tags,author&page[number]=1
```

---

## Server-side: detecting JSON:API requests

### Request::wantsJsonApi()

When building APIs that serve multiple response formats (for example, regular JSON alongside JSON:API), you may need to check whether an incoming request has opted into the JSON:API format by sending the correct `Accept` header.

The `wantsJsonApi` macro is registered on `Illuminate\Http\Request` and returns `true` when the `Accept` header is exactly `application/vnd.api+json`.

```php
use Illuminate\Http\Request;

public function index(Request $request)
{
    if ($request->wantsJsonApi()) {
        return Apiable::response(Post::query())->list();
    }

    return Post::paginate();
}
```

{% hint style="info" %}
Laravel Apiable already uses this check internally when negotiating the response format. You only need to call `wantsJsonApi()` manually when you want to branch your own controller logic based on the client's Accept header.
{% endhint %}

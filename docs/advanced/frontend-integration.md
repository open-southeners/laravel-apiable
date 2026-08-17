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

[Flex URL](https://github.com/open-southeners/flex-url) is an open-source package maintained by Open Southeners that provides an immutable, fluent builder for constructing and parsing URLs that follow this package's request query grammar (`filter`, `sort`, `include`, `fields`, `appends`, `page`, `q`). It runs in the browser and in Node.js.

{% hint style="warning" %}
This section documents the **v2** API surface (`@open-southeners/flex-url`, scoped package name), currently in development. v1 (unscoped `flex-url`) predates this grammar and had several known parsing/encoding defects; don't rely on it against this package's endpoints. This page will be trued up against the real v2 implementation once it ships.
{% endhint %}

- **Repository**: https://github.com/open-southeners/flex-url
- **Documentation**: https://docs.opensoutheners.com/flex-url/

```bash
npm install @open-southeners/flex-url
```

Every builder call returns a **new** immutable instance — nothing is mutated in place, so intermediate values are safe to reuse or store:

```js
import { flexUrl } from '@open-southeners/flex-url'

const url = flexUrl('https://api.example.com/api/v1/posts')
  .filter('status', 'published')            // filter[status]=published
  .filter('title', 'like', 'laravel')        // filter[title][like]=laravel
  .between('created_at', '2026-01-01', '2026-06-01') // filter[created_at][gte]=...&filter[created_at][lte]=...
  .filterScope('featured')                   // filter[scope][featured]=1
  .sort('-created_at')                       // sort=-created_at
  .include('tags', 'author')                 // include=tags,author
  .fields('post', 'title', 'excerpt')        // fields[post]=title,excerpt
  .append('post', 'reading_time')            // appends[post]=reading_time
  .page(1)
  .pageSize(20)

url.toString()
// https://api.example.com/api/v1/posts?filter[status]=published&filter[title][like]=laravel&filter[created_at][gte]=2026-01-01&filter[created_at][lte]=2026-06-01&filter[scope][featured]=1&sort=-created_at&include=tags,author&fields[post]=title,excerpt&appends[post]=reading_time&page[number]=1&page[size]=20
```

Cursor pagination uses `pageCursor()` instead of `page()`:

```js
flexUrl('/api/v1/posts').pageCursor('eyJpZCI6MTB9')
// /api/v1/posts?page[cursor]=eyJpZCI6MTB9
```

#### Parsing is the same as building

Constructing a `FlexUrl` from a full URL or query string round-trips losslessly (pathname, port, and any params it doesn't recognise are preserved), and the same fluent vocabulary reads state back — useful for hydrating UI controls (filters, sort column, page) from `window.location` on page load:

```js
const current = flexUrl(window.location.href)

current.hasFilter('status')       // true
current.getFilter('status')       // 'published'
current.getSort()                 // ['-created_at']
current.getPage()                 // { number: 1, size: 20 }
```

#### Typed against a generated schema

Paired with this package's [`apiable:types` command](../documentation/typescript-schema.md), `flexUrl` narrows `filter()`/`sort()`/`include()`/`fields()`/`append()` arguments to whatever a given endpoint actually allows:

```ts
import { flexUrl } from '@open-southeners/flex-url'
import { apiSchema } from './resources/js/api-schema'

const url = flexUrl<typeof apiSchema.post>('/api/v1/posts')
  .filter('status', 'published') // typo/unknown attribute or value = compile error
  .sort('-created_at')
```

Untyped usage (no generic parameter) continues to work without any generated schema.

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
        return Apiable::response(Post::query());
    }

    return Post::paginate();
}
```

{% hint style="info" %}
Laravel Apiable already uses this check internally when negotiating the response format. You only need to call `wantsJsonApi()` manually when you want to branch your own controller logic based on the client's Accept header.
{% endhint %}

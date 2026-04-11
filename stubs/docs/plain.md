# {{ $resource['name'] }}

{{ $resource['description'] }}

@foreach ($resource['endpoints'] as $endpoint)

---

## {{ $endpoint['title'] }}

@if (!empty($endpoint['auth']))
> **Authentication required:** {{ ucfirst($endpoint['auth']['type']) }} token

@endif
{{ $endpoint['description'] }}

**`{{ $endpoint['method'] }}` `/{{ $endpoint['uri'] }}`**

@if (!empty($endpoint['queryParams']))
### Query Parameters

| Parameter | Values | Description |
|-----------|--------|-------------|
@foreach ($endpoint['queryParams'] as $param)
| `{{ $param['key'] }}` | {{ $param['values'] !== '*' ? '`'.$param['values'].'`' : 'Any' }} | {{ $param['description'] ?: '—' }} |
@endforeach

@endif
### Example Request

```bash
curl -G https://api.example.com/{{ $endpoint['uri'] }} \
  -H "Accept: application/vnd.api+json"@if (!empty($endpoint['auth']) && $endpoint['auth']['type'] === 'bearer') \
  -H "Authorization: Bearer {token}"@elseif (!empty($endpoint['auth']) && $endpoint['auth']['type'] === 'basic') \
  -H "Authorization: Basic {credentials}"@endif

```

@endforeach

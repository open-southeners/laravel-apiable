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

@php
    $isGetRequest = $endpoint['method'] === 'GET';
    $curlHeaders = ['Accept: application/vnd.api+json', 'Content-Type: application/vnd.api+json'];

    if (!empty($endpoint['auth']) && $endpoint['auth']['type'] === 'bearer') {
        $curlHeaders[] = 'Authorization: Bearer {token}';
    } elseif (!empty($endpoint['auth']) && $endpoint['auth']['type'] === 'basic') {
        $curlHeaders[] = 'Authorization: Basic {credentials}';
    }
@endphp
```bash
curl {{ $isGetRequest ? '-G' : '-X '.$endpoint['method'] }} https://api.example.com/{{ $endpoint['uri'] }} \
@foreach ($curlHeaders as $curlHeader)
  -H "{{ $curlHeader }}"@if (!$loop->last || !$isGetRequest) \@endif

@endforeach
@unless ($isGetRequest)
  -d '{"data": {"type": "resource", "attributes": {}}}'
@endunless
```

@endforeach

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ __('Access denied') }}</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#101116;color:#fff;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;text-align:center;padding:1.5rem}p{color:rgba(255,255,255,.75);max-width:32rem;font-size:1.1rem;line-height:1.6}.c{color:#C9A45C;font-weight:800;letter-spacing:.2em;font-size:.8rem}a{display:inline-block;margin-top:1.5rem;background:#C9A45C;color:#101116;padding:.8rem 1.6rem;border-radius:999px;font-weight:700;text-decoration:none}</style></head>
<body><main><p class="c">403</p><h1>{{ __('Access denied') }}</h1><p>{{ __('You do not have permission to view this page.') }}</p><a href="{{ url('/') }}">{{ __('Back to the homepage') }}</a></main></body>
</html>

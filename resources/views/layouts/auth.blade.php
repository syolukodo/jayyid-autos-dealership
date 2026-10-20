<!DOCTYPE html>
<html lang="{{ config('showroom.seo.language') }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') | Jayyid Autos</title>
@hasSection('description')
<meta name="description" content="@yield('description')">
@endif
{{-- Sign-in and set-up pages carry no search value; let crawlers follow links only. --}}
<meta name="robots" content="noindex, follow">
@include('partials.head-assets')
@stack('head')
</head>
<body class="@yield('body-class', 'admin-body')">
@yield('content')

<script src="{{ versioned_asset('assets/js/main.js') }}"></script>
</body>
</html>

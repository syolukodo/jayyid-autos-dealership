{{-- Brand favicon set, fonts and the shared stylesheet, used by every layout. --}}
<link rel="icon" href="{{ asset('assets/images/favicon/favicon.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('assets/images/favicon/favicon-32.png') }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('assets/images/favicon/favicon-16.png') }}" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/images/favicon/site.webmanifest') }}">
<meta name="theme-color" content="#0f2b48">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.cdnfonts.com">
<link rel="stylesheet" href="https://fonts.cdnfonts.com/css/tt-norms">
<link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=chillax@400,500,600,700&display=swap">
<link rel="stylesheet" href="{{ versioned_asset('assets/css/main.css') }}">

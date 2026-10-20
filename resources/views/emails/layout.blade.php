<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;padding:32px 24px;color:#334155">
  <p style="margin:0 0 24px;font-size:18px;font-weight:700;color:#0B2545">{{ config('app.name') }}</p>
  <h1 style="margin:0 0 16px;font-size:20px;color:#0B2545">@yield('heading')</h1>
  @yield('body')
  <p style="margin:32px 0 0;font-size:12px;color:#94a3b8">{{ $business->address }}</p>
</div>

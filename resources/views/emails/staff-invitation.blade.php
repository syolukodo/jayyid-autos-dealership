@extends('emails.layout')

@section('heading', 'You have been added to the team')

@section('body')
<p style="margin:0 0 16px;line-height:1.6">Hi {{ $staff->full_name }},</p>
<p style="margin:0 0 16px;line-height:1.6">
  An administrator has created your {{ config('app.name') }} staff account with the role
  <strong>{{ $staff->role->label() }}</strong>. Set your password to activate it. This link expires in
  {{ config('showroom.staff_invite_ttl_hours') }} hours and can only be used once.
</p>
<p style="margin:24px 0"><a href="{{ $link }}" style="display:inline-block;padding:12px 22px;background:#F05A36;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700">Set my password</a></p>
<p style="margin:0;font-size:13px;line-height:1.6;color:#64748b">You will sign in with {{ $staff->email }}. If you were not expecting this, you can ignore this email.</p>
@endsection

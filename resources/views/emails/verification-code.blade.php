@extends('emails.layout')

@section('heading', 'Confirm your email address')

@section('body')
<p style="margin:0 0 16px;font-size:14px;line-height:1.6">Use this code to confirm your email address and finish creating your account.</p>
<p style="margin:0 0 16px;font-size:30px;font-weight:700;letter-spacing:6px;color:#F95738">{{ $code }}</p>
<p style="margin:0;font-size:13px;line-height:1.6;color:#64748b">The code expires in {{ $minutes }} minutes. If you did not request it, you can ignore this email.</p>
@endsection

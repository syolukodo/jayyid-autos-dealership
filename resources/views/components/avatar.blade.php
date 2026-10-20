@props(['user', 'class', 'photoClass'])
{{-- The profile photo when one is saved, otherwise the first initial. --}}
@if ($user?->avatar)
  <span class="{{ $class }} {{ $photoClass }}" aria-hidden="true"><img src="{{ str_starts_with($user->avatar, 'data:') ? $user->avatar : asset($user->avatar) }}" alt=""></span>
@else
  <span class="{{ $class }}" aria-hidden="true">{{ $user?->initial() ?? 'A' }}</span>
@endif

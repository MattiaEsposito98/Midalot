@props(['user'])

{{ $user?->nickname ?? '-' }}
@if ($user?->latestMonthlyBadge)
    <span class="badge bg-warning text-dark" title="{{ $user->latestMonthlyBadge->label }}">
        <i class="bi bi-award-fill"></i> {{ $user->latestMonthlyBadge->label }}
    </span>
@endif

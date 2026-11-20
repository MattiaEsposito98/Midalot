@php
    $exportParams = array_filter($filters, fn ($v) => $v !== '');
    $hasFilters = collect($filters)->filter(fn ($v) => $v !== '')->isNotEmpty();
@endphp
<div id="ajax-payload" data-total="{{ $prospects->total() }}" data-has-filters="{{ $hasFilters ? 1 : 0 }}"
    data-due="{{ $dueCount }}" data-total-all="{{ $totalAll }}" data-export-url="{{ route('admin.prospects.export', $exportParams) }}">
    <div data-part="tabs">@include('admin.prospects._tabs')</div>
    <div data-part="table">@include('admin.prospects._table')</div>
</div>

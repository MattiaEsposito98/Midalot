@php
    use App\Models\Prospect;

    // Link che mantiene i filtri attuali e cambia solo alcuni parametri.
    $keep = fn (array $extra = []) => array_filter(
        array_merge($filters, ['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage === 50 ? null : $perPage], $extra),
        fn ($v) => $v !== null && $v !== ''
    );
@endphp

            <div class="admin-page-actions mb-3">
                <a href="{{ route('admin.prospects.index', $keep(['status' => null, 'follow_up' => null, 'page' => null])) }}"
                    class="btn btn-sm {{ $filters['status'] === '' && $filters['follow_up'] !== 'due' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Tutti <span class="badge bg-secondary ms-1">{{ $statusCounts->sum() }}</span>
                </a>
                @foreach (Prospect::STATUSES as $key => $label)
                    <a href="{{ route('admin.prospects.index', $keep(['status' => $key, 'page' => null])) }}"
                        class="btn btn-sm {{ $filters['status'] === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }} <span class="badge bg-secondary ms-1">{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
                <a href="{{ route('admin.prospects.index', $keep(['follow_up' => 'due', 'status' => null, 'page' => null])) }}"
                    class="btn btn-sm {{ $filters['follow_up'] === 'due' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="bi bi-alarm"></i> Da ricontattare <span class="badge bg-danger ms-1">{{ $dueCount }}</span>
                </a>
            </div>

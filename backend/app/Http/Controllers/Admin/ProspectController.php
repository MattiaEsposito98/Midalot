<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Models\ProspectSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Clienti da contattare": attivita' trovate su Instagram a cui proporre i servizi digitali.
 * Filtri, ordinamento e paginazione sono fatti dal database (nessun caricamento in blocco).
 */
class ProspectController extends Controller
{
    private const PER_PAGE = 50;

    /** Colonne ordinabili da ?sort= (chiave = parametro, valore = colonna reale). */
    private const SORTABLE = [
        'followers' => 'followers',
        'name' => 'name',
        'region' => 'region',
        'city' => 'city',
        'priority' => 'priority',
        'contacted_at' => 'contacted_at',
        'follow_up_at' => 'follow_up_at',
        'created_at' => 'created_at',
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);

        $sort = array_key_exists($request->query('sort'), self::SORTABLE) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $prospects = (clone $query)
            ->with(['search:id,name', 'contactedBy:id,name'])
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Contatori per stato (una sola query, indipendenti dal filtro di stato).
        $statusCounts = $this->filteredQuery(array_merge($filters, ['status' => '']))
            ->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.prospects.index', [
            'prospects' => $prospects,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'statusCounts' => $statusCounts,
            'totalAll' => Prospect::count(),
            'dueCount' => $this->dueQuery()->count(),
            'regions' => Prospect::REGIONS,
            'cities' => Prospect::whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'sectors' => Prospect::whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
            'searches' => ProspectSearch::orderByDesc('id')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('admin.prospects.form', $this->formData(new Prospect(['priority' => 'B'])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->applyContactRules($data, null, $request);

        $prospect = Prospect::create($data);

        return redirect()->route('admin.prospects.edit', $prospect)->with('success', 'Profilo aggiunto.');
    }

    public function edit(Prospect $prospect)
    {
        return view('admin.prospects.form', $this->formData($prospect));
    }

    public function update(Request $request, Prospect $prospect)
    {
        $data = $this->validated($request, $prospect);
        $data = $this->applyContactRules($data, $prospect, $request);

        $prospect->update($data);

        return redirect()->route('admin.prospects.edit', $prospect)->with('success', 'Profilo aggiornato.');
    }

    /** Cambio rapido di stato (e promemoria) direttamente dall'elenco. */
    public function status(Request $request, Prospect $prospect)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Prospect::STATUSES))],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $changes = ['status' => $data['status']];
        if ($request->has('follow_up_at')) {
            $changes['follow_up_at'] = ($data['follow_up_at'] ?? null) ?: null;
        }

        $prospect->fill($this->applyContactRules($changes, $prospect, $request))->save();

        return back()->with('success', "{$prospect->instagram_handle}: stato aggiornato.");
    }

    public function destroy(Prospect $prospect)
    {
        $handle = $prospect->instagram_handle;
        $prospect->delete();

        return redirect()->route('admin.prospects.index')->with('success', "Profilo {$handle} eliminato.");
    }

    /** Esporta in CSV (apribile in Excel) i profili del filtro corrente. */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($this->filters($request))->with('search:id,name')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel legge bene le lettere accentate
            fputcsv($out, ['Profilo', 'Link Instagram', 'Nome', 'Follower', 'Regione', 'Citta', 'Settore', 'Sito web', 'Email', 'Telefono',
                'Negozio fisico', 'Ordini in DM', 'Stato sito', 'Attivita', 'Proposta', 'Priorita', 'Stato', 'Contattato il', 'Promemoria', 'Ricerca', 'Segnali', 'Note'], ';');
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [
                        '@' . $p->instagram_handle, $p->instagram_url, $p->name, $p->followers, $p->region, $p->city, $p->sector,
                        $p->website_url, $p->email, $p->phone,
                        $p->has_physical_store === null ? '' : ($p->has_physical_store ? 'si' : 'no'),
                        $p->orders_in_dm === null ? '' : ($p->orders_in_dm ? 'si' : 'no'),
                        Prospect::WEBSITE_STATUSES[$p->website_status] ?? '', Prospect::ACTIVITIES[$p->activity] ?? '',
                        Prospect::PROPOSALS[$p->proposal] ?? '', $p->priority, Prospect::STATUSES[$p->status] ?? '',
                        optional($p->contacted_at)->format('d/m/Y'), optional($p->follow_up_at)->format('d/m/Y'),
                        optional($p->search)->name, $p->signals, $p->notes,
                    ], ';');
                }
            });
            fclose($out);
        }, 'clienti-da-contattare-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---------------------------------------------------------------------

    /** @return array<string, string> */
    private function filters(Request $request): array
    {
        $keys = ['q', 'region', 'city', 'sector', 'status', 'priority', 'proposal', 'website_status', 'activity', 'contacted', 'follow_up', 'search_id', 'min_followers', 'max_followers'];
        $filters = [];
        foreach ($keys as $key) {
            $value = $request->query($key);
            $filters[$key] = is_string($value) ? trim($value) : '';
        }

        return $filters;
    }

    private function filteredQuery(array $f): Builder
    {
        return Prospect::query()
            ->when($f['q'] !== '', function (Builder $q) use ($f) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], ltrim($f['q'], '@')) . '%';
                $q->where(fn (Builder $q) => $q->where('instagram_handle', 'like', $like)->orWhere('name', 'like', $like)->orWhere('notes', 'like', $like));
            })
            ->when($f['region'] !== '', fn (Builder $q) => $q->where('region', $f['region']))
            ->when($f['city'] !== '', fn (Builder $q) => $q->where('city', $f['city']))
            ->when($f['sector'] !== '', fn (Builder $q) => $q->where('sector', $f['sector']))
            ->when($f['status'] !== '', fn (Builder $q) => $q->where('status', $f['status']))
            ->when($f['priority'] !== '', fn (Builder $q) => $q->where('priority', $f['priority']))
            ->when($f['proposal'] !== '', fn (Builder $q) => $q->where('proposal', $f['proposal']))
            ->when($f['website_status'] !== '', fn (Builder $q) => $q->where('website_status', $f['website_status']))
            ->when($f['activity'] !== '', fn (Builder $q) => $q->where('activity', $f['activity']))
            ->when($f['search_id'] !== '', fn (Builder $q) => $q->where('prospect_search_id', (int) $f['search_id']))
            ->when(ctype_digit($f['min_followers']), fn (Builder $q) => $q->where('followers', '>=', (int) $f['min_followers']))
            ->when(ctype_digit($f['max_followers']), fn (Builder $q) => $q->where('followers', '<=', (int) $f['max_followers']))
            ->when($f['contacted'] === 'yes', fn (Builder $q) => $q->whereNotNull('contacted_at'))
            ->when($f['contacted'] === 'no', fn (Builder $q) => $q->whereNull('contacted_at'))
            ->when($f['follow_up'] === 'due', fn (Builder $q) => $q->whereNotNull('follow_up_at')->whereDate('follow_up_at', '<=', today())->whereNotIn('status', Prospect::CLOSED_STATUSES))
            ->when($f['follow_up'] === 'upcoming', fn (Builder $q) => $q->whereDate('follow_up_at', '>', today())->whereNotIn('status', Prospect::CLOSED_STATUSES))
            ->when($f['follow_up'] === 'none', fn (Builder $q) => $q->whereNull('follow_up_at'));
    }

    public static function dueQuery(): Builder
    {
        return Prospect::query()->whereNotNull('follow_up_at')->whereDate('follow_up_at', '<=', today())->whereNotIn('status', Prospect::CLOSED_STATUSES);
    }

    private function formData(Prospect $prospect): array
    {
        return [
            'prospect' => $prospect,
            'regions' => Prospect::REGIONS,
            'sectors' => Prospect::whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
            'searches' => ProspectSearch::orderByDesc('id')->get(['id', 'name']),
        ];
    }

    private function validated(Request $request, ?Prospect $prospect = null): array
    {
        $request->merge(['instagram_handle' => Prospect::normalizeHandle((string) $request->input('instagram_handle'))]);

        $data = $request->validate([
            'instagram_handle' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9._]+$/', Rule::unique('prospects', 'instagram_handle')->ignore($prospect?->id)],
            'name' => ['nullable', 'string', 'max:150'],
            'followers' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'region' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'sector' => ['nullable', 'string', 'max:60'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'has_physical_store' => ['nullable', 'in:0,1'],
            'orders_in_dm' => ['nullable', 'in:0,1'],
            'website_status' => ['required', Rule::in(array_keys(Prospect::WEBSITE_STATUSES))],
            'activity' => ['required', Rule::in(array_keys(Prospect::ACTIVITIES))],
            'signals' => ['nullable', 'string', 'max:2000'],
            'proposal' => ['nullable', Rule::in(array_keys(Prospect::PROPOSALS))],
            'priority' => ['required', Rule::in(array_keys(Prospect::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(Prospect::STATUSES))],
            'follow_up_at' => ['nullable', 'date'],
            'prospect_search_id' => ['nullable', 'exists:prospect_searches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'instagram_handle.regex' => 'Il nome del profilo puo\' contenere solo lettere minuscole, numeri, punto e trattino basso.',
            'instagram_handle.unique' => 'Questo profilo e\' gia\' nell\'elenco.',
        ]);

        $data['website_url'] = Prospect::safeUrl($data['website_url'] ?? null);
        foreach (['has_physical_store', 'orders_in_dm'] as $flag) {
            $data[$flag] = ($data[$flag] ?? '') === '' ? null : (bool) $data[$flag];
        }
        $data['follow_up_at'] = ($data['follow_up_at'] ?? null) ?: null;

        return $data;
    }

    /**
     * Quando un profilo esce da "Da contattare" si ricorda quando e da chi (una sola volta);
     * se torna a "Da contattare" la data resta, cosi' non si perde lo storico.
     */
    private function applyContactRules(array $data, ?Prospect $prospect, Request $request): array
    {
        $newStatus = $data['status'] ?? $prospect?->status ?? 'to_contact';
        $alreadyContacted = $prospect?->contacted_at !== null;

        if ($newStatus !== 'to_contact' && ! $alreadyContacted) {
            $data['contacted_at'] = now();
            $data['contacted_by'] = $request->user()?->id;
        }

        // Un profilo chiuso (cliente o non idoneo) non ha piu' bisogno del promemoria.
        if (in_array($newStatus, Prospect::CLOSED_STATUSES, true)) {
            $data['follow_up_at'] = null;
        }

        return $data;
    }
}

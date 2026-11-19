<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Models\ProspectSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Operazioni di ricerca: regione + settore + data, per sapere cosa e' gia' stato fatto. */
class ProspectSearchController extends Controller
{
    public function index(Request $request)
    {
        $region = trim((string) $request->query('region', ''));
        $status = trim((string) $request->query('status', ''));

        $searches = ProspectSearch::query()
            ->withCount([
                'prospects',
                'prospects as contacted_count' => fn ($q) => $q->whereNotNull('contacted_at'),
            ])
            ->when($region !== '', fn ($q) => $q->where('region', $region))
            ->when(array_key_exists($status, ProspectSearch::STATUSES), fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        // Copertura per regione: ricerche fatte, profili trovati e contattati.
        $coverage = DB::table('prospect_searches as s')
            ->leftJoin('prospects as p', 'p.prospect_search_id', '=', 's.id')
            ->whereNotNull('s.region')
            ->groupBy('s.region')
            ->selectRaw("s.region, COUNT(DISTINCT s.id) as searches, COUNT(DISTINCT CASE WHEN s.status = 'done' THEN s.id END) as done_searches, COUNT(p.id) as prospects, COUNT(p.contacted_at) as contacted")
            ->orderBy('s.region')
            ->get();

        return view('admin.prospects.searches', [
            'searches' => $searches,
            'coverage' => $coverage,
            'regions' => Prospect::REGIONS,
            'sectors' => ProspectSearch::whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
            'filters' => compact('region', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = ($data['name'] ?? null) ?: $this->defaultName($data);

        ProspectSearch::create($data);

        return back()->with('success', 'Ricerca aggiunta.');
    }

    public function update(Request $request, ProspectSearch $search)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ProspectSearch::STATUSES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'done' && ! $search->searched_at) {
            $data['searched_at'] = today();
        }

        $search->update($data);

        return back()->with('success', 'Ricerca aggiornata.');
    }

    public function destroy(ProspectSearch $search)
    {
        // I profili restano nell'elenco: si scollegano soltanto dalla ricerca (nullOnDelete).
        $search->delete();

        return back()->with('success', 'Ricerca eliminata (i profili restano nell\'elenco).');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'region' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'sector' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(array_keys(ProspectSearch::STATUSES))],
            'searched_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['searched_at'] = ($data['searched_at'] ?? null) ?: null;

        return $data;
    }

    /** "Campania · Napoli · Abbigliamento · 19/11/2026" */
    private function defaultName(array $data): string
    {
        return collect([$data['region'] ?? null, $data['city'] ?? null, $data['sector'], (! empty($data['searched_at']) ? \Carbon\Carbon::parse($data['searched_at']) : now())->format('d/m/Y')])
            ->filter()->implode(' · ');
    }
}

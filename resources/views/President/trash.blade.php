@extends('President.layouts.master')

@section('body')
    <div class="space-y-6">
        <div class="visitx-hero">
            <div>
                <p class="visitx-eyebrow">Administration</p>
                <h1 class="page-title">Corbeille</h1>
                <p class="page-subtitle">Visites supprimees conservees pendant 30 jours avant suppression definitive.</p>
            </div>
            <div class="visitx-hero-side">
                <div class="visitx-hero-chip">
                    <span class="visitx-hero-dot"></span>
                    {{ $deletedVisits->count() }} visite(s)
                </div>
            </div>
        </div>

        <section class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Visiteur</th>
                            <th>Service / Antenne</th>
                            <th>Date entree</th>
                            <th>Date suppression</th>
                            <th>Suppression definitive</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deletedVisits as $visit)
                            <tr>
                                <td class="px-5 py-4"><span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $visit->type }}</span></td>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ trim(($visit->firstname ?? '') . ' ' . ($visit->lastname ?? '')) ?: 'Visiteur inconnu' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $visit->service_name ?: 'Non renseigne' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $visit->entry_date ?: 'Non renseignee' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $visit->deleted_at ?: 'Date non disponible' }}</td>
                                <td class="px-5 py-4 text-sm font-semibold text-rose-600">
                                    {{ $visit->deleted_at ? \Carbon\Carbon::parse($visit->deleted_at)->addDays(30)->format('d/m/Y H:i') : 'Apres 30 jours' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form action="{{ route('p_restore_visit', ['type' => strtolower($visit->type), 'id' => $visit->id]) }}" method="POST" onsubmit="return confirm('Recuperer cette visite ?');">
                                        @csrf
                                        <button type="submit" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                            Recuperer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">La corbeille est vide.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

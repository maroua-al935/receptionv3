@extends('President.layouts.master')

@section('body')
    <div class="space-y-6">
        <div class="visitx-hero">
            <div>
                <p class="visitx-eyebrow">President</p>
                <h1 class="page-title">Visiteurs siege</h1>
                <p class="page-subtitle">Vue du jour des visiteurs traites au siege avec acces rapide au detail.</p>
            </div>
            <div class="visitx-hero-side">
                <div class="visitx-hero-chip">
                    <span class="visitx-hero-dot"></span>
                    Aujourd'hui
                </div>
            </div>
        </div>

        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Liste des visiteurs siege</h2>
                    <p class="text-sm text-slate-500">Suivi quotidien des passages et de leur statut.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">{{ $data->count() }} ligne(s)</span>
            </div>

            @if(!$data->isEmpty())
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Num</th>
                                <th>Visiteur</th>
                                <th>Hote</th>
                                <th>Date entree</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = $data->count() + 1; @endphp
                            @foreach($data as $row)
                                @php $i--; @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-slate-700">{{ $i }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                        <div class="text-sm text-slate-500">{{ $row->org_name }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-sm font-medium text-slate-900">{{ $row->emp_visited }}</div>
                                        <div class="text-sm text-slate-500">{{ $row->service_name }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                                    <td class="px-5 py-4">
                                        @switch($row->status)
                                            @case(0)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">En attente</span>@break
                                            @case(1)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">En cours</span>@break
                                            @case(2)<span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Terminee</span>@break
                                        @endswitch
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a class="inline-flex rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" href="{{ route('i_info', $row->id) }}">
                                            Details complets
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-14 text-center">
                    <p class="font-semibold text-slate-700">Aucun visiteur siege aujourd'hui.</p>
                    <p class="mt-1 text-sm text-slate-500">Les nouvelles visites apparaitront ici automatiquement.</p>
                </div>
            @endif
        </section>
    </div>
@endsection

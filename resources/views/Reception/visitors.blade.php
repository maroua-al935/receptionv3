@extends('Reception.layouts.master')

@section('body')
    @php
        $isBadgeAgent = Auth::guard('web')->user()->name === 'agent_saisie_badge' || Auth::guard('web')->user()->email === 'agent_saisie_badge@visilog.local';
    @endphp
    <div class="space-y-6">
        <div class="visitx-hero">
            <div>
                <p class="visitx-eyebrow">Reception</p>
                <h1 class="page-title">Visiteurs</h1>
                <p class="page-subtitle">Suivi des passages du jour et des anciennes visites non cloturees.</p>
            </div>
            <div class="visitx-hero-side">
                <div class="visitx-hero-chip">
                    <span class="visitx-hero-dot"></span>
                    File du jour
                </div>
                @if((int) Auth::guard('web')->user()->profile === 5)
                    <a href="{{ route('i_add_visitors') }}" class="primary-action inline-flex w-fit items-center gap-2">
                        <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M11 13H5v-2h6V5h2v6h6v2h-6v6h-2z"/></svg>
                        Ajouter un visiteur
                    </a>
                @endif
            </div>
        </div>

        <section class="panel">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Aujourd'hui</h2>
                    <p class="text-sm text-slate-500">Visites enregistrees pour la journee en cours.</p>
                </div>
                <span class="rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700">{{ $data->count() }} visite(s)</span>
            </div>

            @if(!$data->isEmpty())
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Num</th>
                                <th>Badge</th>
                                <th>Visiteur</th>
                                <th>Hote</th>
                                <th>Date entree</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = $data->count() + 1; @endphp
                            @foreach($data as $row)
                                @php $i--; @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-slate-700">{{ $i }}</td>
                                    <td class="px-5 py-4 visitx-badge-col"><span class="visitx-badge-pill visitx-badge-pill-soft">{{ $row->badge_n ?: '-' }}</span></td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12q-1.65 0-2.825-1.175T8 8t1.175-2.825T12 4t2.825 1.175T16 8t-1.175 2.825T12 12m-8 8v-2.8q0-.85.438-1.562T5.6 14.55q1.55-.775 3.15-1.162T12 13t3.25.388t3.15 1.162q.725.375 1.163 1.088T20 17.2V20z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                                <div class="text-sm text-slate-500">{{ $row->org_name }}</div>
                                            </div>
                                        </div>
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
                                            @case(3)<span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">Badge a recuperer</span>@break
                                        @endswitch
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_info',$row->id) }}" title="Details complets">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                            </a>
                                            @if($isBadgeAgent)
                                                <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_edit_visitors',$row->id) }}" title="Badge">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m19.3 8.925l-4.25-4.2l1.4-1.4q.575-.575 1.413-.575t1.412.575l1.4 1.4q.575.575.6 1.388t-.55 1.387zM17.85 10.4L7.25 21H3v-4.25l10.6-10.6z"/></svg>
                                                </a>
                                            @elseif((int) Auth::guard('web')->user()->profile !== 8 || !in_array((int) $row->status, [2, 3], true))
                                                <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_edit_visitors',$row->id) }}" title="{{ (int) Auth::guard('web')->user()->profile === 8 ? 'Orienter' : 'Modifier' }}">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m19.3 8.925l-4.25-4.2l1.4-1.4q.575-.575 1.413-.575t1.412.575l1.4 1.4q.575.575.6 1.388t-.55 1.387zM17.85 10.4L7.25 21H3v-4.25l10.6-10.6z"/></svg>
                                                </a>
                                            @endif
                                            @if($row->status == 3)
                                                <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="status" value="2">
                                                    <button class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100" type="submit">
                                                        Cloturer
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold text-slate-700">Aucune visite aujourd'hui</p>
                    <p class="mt-1 text-sm text-slate-500">Ajoutez un visiteur pour demarrer la liste.</p>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Anciennes donnees</h2>
                    <p class="text-sm text-slate-500">Visites anterieures toujours en attente.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">{{ $old->count() }} visite(s)</span>
            </div>

            @if(!$old->isEmpty())
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Num</th>
                                <th>Badge</th>
                                <th>Visiteur</th>
                                <th>Hote</th>
                                <th>H. arrivee</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = $old->count() + 1; @endphp
                            @foreach($old as $row)
                                @php $i--; @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-slate-700">{{ $i }}</td>
                                    <td class="px-5 py-4 visitx-badge-col"><span class="visitx-badge-pill visitx-badge-pill-soft">{{ $row->badge_n ?: '-' }}</span></td>
                                    <td class="px-5 py-4"><div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div><div class="text-sm text-slate-500">{{ $row->org_name }}</div></td>
                                    <td class="px-5 py-4"><div class="text-sm font-medium text-slate-900">{{ $row->emp_visited }}</div><div class="text-sm text-slate-500">{{ $row->service_name }}</div></td>
                                    <td class="px-5 py-4 text-sm text-slate-500">{{ $row->entry_date }}</td>
                                    <td class="px-5 py-4">
                                        @switch($row->status)
                                            @case(0)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">En attente</span>@break
                                            @case(1)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">En cours</span>@break
                                            @case(2)<span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Terminee</span>@break
                                            @case(3)<span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">Badge a recuperer</span>@break
                                        @endswitch
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_info',$row->id) }}" title="Details complets">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                            </a>
                                            @if($isBadgeAgent)
                                                <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_edit_visitors',$row->id) }}" title="Badge">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m19.3 8.925l-4.25-4.2l1.4-1.4q.575-.575 1.413-.575t1.412.575l1.4 1.4q.575.575.6 1.388t-.55 1.387zM17.85 10.4L7.25 21H3v-4.25l10.6-10.6z"/></svg>
                                                </a>
                                            @elseif((int) Auth::guard('web')->user()->profile !== 8 || !in_array((int) $row->status, [2, 3], true))
                                                <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_edit_visitors',$row->id) }}" title="{{ (int) Auth::guard('web')->user()->profile === 8 ? 'Orienter' : 'Modifier' }}">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m19.3 8.925l-4.25-4.2l1.4-1.4q.575-.575 1.413-.575t1.412.575l1.4 1.4q.575.575.6 1.388t-.55 1.387zM17.85 10.4L7.25 21H3v-4.25l10.6-10.6z"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold text-slate-700">Aucune ancienne visite</p>
                    <p class="mt-1 text-sm text-slate-500">Toutes les visites precedentes sont cloturees.</p>
                </div>
            @endif
        </section>
    </div>
@endsection


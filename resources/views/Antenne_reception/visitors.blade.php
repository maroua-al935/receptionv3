@extends('Antenne_reception.layouts.master')

@section('body')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-600">Registre antenne</p>
                <h1 class="page-title">Visiteurs</h1>
                <p class="page-subtitle">Passages du jour et anciennes visites non cloturees pour {{ $loc ?? 'cette antenne' }}.</p>
            </div>
            @if((int) Auth::guard('web')->user()->profile === 6)
                <a href="{{ route('i_ant_add_visitors') }}" class="antenne-action">
                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M11 13H5v-2h6V5h2v6h6v2h-6v6h-2z"/></svg>
                    Ajouter un visiteur
                </a>
            @endif
        </div>

        <section class="panel antenne-panel">
            <div class="antenne-panel-head">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-900">Aujourd'hui</h2>
                    <p class="text-xs font-medium text-slate-500">Visites enregistrees pour la journee en cours.</p>
                </div>
                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700">{{ $data->count() }} visite(s)</span>
            </div>

            @if(!$data->isEmpty())
                <div class="overflow-x-auto">
                    <table class="modern-table antenne-table">
                        <thead>
                            <tr>
                                <th>Num</th>
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
                                    <td class="px-5 py-4 text-sm font-bold text-slate-700">{{ $i }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12q-1.65 0-2.825-1.175T8 8t1.175-2.825T12 4t2.825 1.175T16 8t-1.175 2.825T12 12m-8 8v-2.8q0-.85.438-1.562T5.6 14.55q1.55-.775 3.15-1.162T12 13t3.25.388t3.15 1.162q.725.375 1.163 1.088T20 17.2V20z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                                <div class="text-xs font-medium text-slate-500">{{ $row->org_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-sm font-bold text-slate-900">{{ $row->emp_visited }}</div>
                                        <div class="text-xs font-medium text-rose-600">Antenne {{ $row->ant_name }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-medium text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                                    <td class="px-5 py-4">
                                        @switch($row->status)
                                            @case(0)<span class="status-chip status-waiting">En attente</span>@break
                                            @case(1)<span class="status-chip status-progress">En cours</span>@break
                                            @case(2)<span class="status-chip status-done">Terminee</span>@break
                                            @case(3)<span class="status-chip status-move">Deplacement</span>@break
                                        @endswitch
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a class="antenne-icon-btn" href="{{ route('i_ant_info',$row->id) }}" title="Details complets">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                            </a>
                                            @if((int) Auth::guard('web')->user()->profile === 6 || (($isAntenneHead ?? false) && empty($row->emp_visited)))
                                                <a class="antenne-icon-btn" href="{{ route('i_edit_visitors',$row->id) }}" title="Modifier">
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
                    <p class="font-black text-slate-700">Aucune visite aujourd'hui</p>
                    <p class="mt-1 text-sm text-slate-500">Ajoutez un visiteur pour demarrer le registre de l'antenne.</p>
                </div>
            @endif
        </section>

        <section class="panel antenne-panel">
            <div class="antenne-panel-head">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-900">Anciennes donnees</h2>
                    <p class="text-xs font-medium text-slate-500">Visites anterieures toujours en attente.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">{{ $old->count() }} visite(s)</span>
            </div>

            @if(!$old->isEmpty())
                <div class="overflow-x-auto">
                    <table class="modern-table antenne-table">
                        <thead>
                            <tr>
                                <th>Num</th>
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
                                    <td class="px-5 py-4 text-sm font-bold text-slate-700">{{ $i }}</td>
                                    <td class="px-5 py-4"><div class="font-bold text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div><div class="text-xs font-medium text-slate-500">{{ $row->org_name }}</div></td>
                                    <td class="px-5 py-4"><div class="text-sm font-bold text-slate-900">{{ $row->emp_visited }}</div><div class="text-xs font-medium text-rose-600">Antenne {{ $row->ant_name }}</div></td>
                                    <td class="px-5 py-4 text-sm font-medium text-slate-500">{{ $row->entry_date }}</td>
                                    <td class="px-5 py-4">
                                        @switch($row->status)
                                            @case(0)<span class="status-chip status-waiting">En attente</span>@break
                                            @case(1)<span class="status-chip status-progress">En cours</span>@break
                                            @case(2)<span class="status-chip status-done">Terminee</span>@break
                                            @case(3)<span class="status-chip status-move">Deplacement</span>@break
                                        @endswitch
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a class="antenne-icon-btn" href="{{ route('i_ant_info',$row->id) }}" title="Details complets">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                            </a>
                                            @if((int) Auth::guard('web')->user()->profile === 6 || (($isAntenneHead ?? false) && empty($row->emp_visited)))
                                                <a class="antenne-icon-btn" href="{{ route('i_edit_visitors',$row->id) }}" title="Modifier">
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
                    <p class="font-black text-slate-700">Aucune ancienne visite</p>
                    <p class="mt-1 text-sm text-slate-500">Toutes les visites precedentes sont cloturees.</p>
                </div>
            @endif
        </section>
    </div>
@endsection

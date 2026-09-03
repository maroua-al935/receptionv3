@extends('Service.layouts.master')

@section('body')
    @php
    @endphp
    <div class="space-y-6">
     

        <section class="panel">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Liste d'attente</h2>
                    <p class="text-sm text-slate-500">Visites enregistrees pour la journee en cours.</p>
                </div>
                <span class="rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700">{{ $data->count() }} visite(s)</span>
            </div>

            @if(!$data->isEmpty())
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th class="text-center">NUM</th>
                                <th class="visitx-badge-col">BADGE</th>
                                <th>VISITEUR</th>
                                <th>HOTE</th>
                                <th>DATE ENTREE</th>
                                <th>STATUS</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = $data->count() + 1; @endphp
                            @foreach($data as $row)
                                @php $i--; @endphp
                                <tr>
                                    <td class="px-5 py-4 text-center text-sm font-medium text-slate-700">{{ $i }}</td>
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
                                        <a class="service-details-open rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_info',$row->id) }}" title="Details complets" data-details-url="{{ route('i_info',$row->id) }}?embed=1">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                            </a>
                                            @if((int) Auth::guard('web')->user()->profile !== 8 || !in_array((int) $row->status, [2, 3], true))
                                                <a class="{{ (int) Auth::guard('web')->user()->profile === 4 && !($headServiceIds ?? collect())->contains((int) $row->service_id) ? 'hidden' : '' }} rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_edit_visitors',$row->id) }}" title="{{ (int) Auth::guard('web')->user()->profile === 8 ? 'Orienter' : 'Modifier' }}" @if((int) Auth::guard('web')->user()->profile === 8) data-orientation-open data-orientation-url="{{ route('i_edit_visitors',$row->id) }}" @endif>
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
                                <th class="text-center">NUM</th>
                                <th class="visitx-badge-col">BADGE</th>
                                <th>VISITEUR</th>
                                <th>HOTE</th>
                                <th>H. ARRIVEE</th>
                                <th>STATUS</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = $old->count() + 1; @endphp
                            @foreach($old as $row)
                                @php $i--; @endphp
                                <tr>
                                    <td class="px-5 py-4 text-center text-sm font-medium text-slate-700">{{ $i }}</td>
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
                                        <a class="service-details-open rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_info',$row->id) }}" title="Details complets" data-details-url="{{ route('i_info',$row->id) }}?embed=1">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                            </a>
                                            @if((int) Auth::guard('web')->user()->profile !== 8 || !in_array((int) $row->status, [2, 3], true))
                                                <a class="{{ (int) Auth::guard('web')->user()->profile === 4 && !($headServiceIds ?? collect())->contains((int) $row->service_id) ? 'hidden' : '' }} rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700" href="{{ route('i_edit_visitors',$row->id) }}" title="{{ (int) Auth::guard('web')->user()->profile === 8 ? 'Orienter' : 'Modifier' }}" @if((int) Auth::guard('web')->user()->profile === 8) data-orientation-open data-orientation-url="{{ route('i_edit_visitors',$row->id) }}" @endif>
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

    @if(in_array((int) Auth::guard('web')->user()->profile, [4, 9, 10], true))
        <div id="service-visitors-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4" aria-hidden="true">
            <div class="relative flex w-full max-w-none flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="width: 96vw; height: 94vh;" role="dialog" aria-modal="true" aria-labelledby="service-visitors-details-title">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 id="service-visitors-details-title" class="text-lg font-semibold text-slate-900">Details complets de la visite</h2>
                    <button type="button" data-service-visitors-details-close class="rounded-lg border border-slate-200 px-3 py-1 text-2xl leading-none text-slate-600 hover:bg-slate-100" aria-label="Fermer">&times;</button>
                </div>
                <iframe id="service-visitors-details-frame" class="min-h-0 flex-1 w-full border-0" title="Details complets de la visite"></iframe>
            </div>
        </div>
        <script>
            (() => {
                const modal = document.getElementById('service-visitors-details-modal');
                const frame = document.getElementById('service-visitors-details-frame');
                if (!modal || !frame) return;
                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    modal.setAttribute('aria-hidden', 'true');
                    frame.src = 'about:blank';
                };
                document.querySelectorAll('[data-details-url]').forEach((link) => link.addEventListener('click', (event) => {
                    event.preventDefault();
                    frame.src = link.dataset.detailsUrl;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    modal.setAttribute('aria-hidden', 'false');
                }));
                modal.querySelector('[data-service-visitors-details-close]').addEventListener('click', close);
                modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
                document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
            })();
        </script>
    @endif

    @if((int) Auth::guard('web')->user()->profile === 8)
        <style>
            .orientation-modal { display: none; position: fixed; inset: 0; z-index: 60; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .62); }
            .orientation-modal.is-open { display: flex; }
            .orientation-dialog { position: relative; width: min(980px, 100%); height: min(850px, 94vh); overflow: hidden; border-radius: 18px; background: #fff; box-shadow: 0 24px 70px rgba(15, 23, 42, .3); }
            .orientation-close { position: absolute; top: 12px; right: 14px; z-index: 1; width: 38px; height: 38px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #475569; cursor: pointer; font-size: 25px; line-height: 1; }
            .orientation-close:hover, .orientation-close:focus-visible { color: #dc2626; outline: 2px solid #fecaca; }
            .orientation-frame { width: 100%; height: 100%; border: 0; }
        </style>
        <div class="orientation-modal" data-orientation-modal aria-hidden="true">
            <div class="orientation-dialog" role="dialog" aria-modal="true" aria-label="Orienter la visite">
                <button type="button" class="orientation-close" data-orientation-close aria-label="Fermer">&times;</button>
                <iframe class="orientation-frame" data-orientation-frame title="Orienter la visite"></iframe>
            </div>
        </div>
        <script>
            (() => {
                const modal = document.querySelector('[data-orientation-modal]');
                const frame = document.querySelector('[data-orientation-frame]');
                if (!modal || !frame) return;
                const close = () => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); frame.src = ''; document.body.style.overflow = ''; };
                document.querySelectorAll('[data-orientation-open]').forEach((link) => link.addEventListener('click', (event) => {
                    event.preventDefault();
                    frame.src = link.dataset.orientationUrl;
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }));
                modal.querySelector('[data-orientation-close]').addEventListener('click', close);
                modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
                document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
            })();
        </script>
    @endif
@endsection


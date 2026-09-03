@extends('Service.layouts.master')

@section('body')
    @php
        $serviceCards = [
            ['label' => "Visiteurs service aujourd'hui", 'value' => $today, 'tag' => '2% up', 'tone' => 'primary'],
            ['label' => 'Service en attente', 'value' => $waiting, 'tag' => '1% down', 'tone' => 'warning'],
            ['label' => 'Service en cours', 'value' => $progress, 'tag' => '3% up', 'tone' => 'success'],
            ['label' => 'Service terminees', 'value' => $finished, 'tag' => 'Stable', 'tone' => 'slate'],
        ];

        $maxCardValue = max(1, $today, $waiting, $progress, $finished);
        $recentVisitors = $data->take(5);
        $isTodayPeriod = true;
        $isFiscalAgent = (int) Auth::guard('web')->user()->profile === 10;
        $oldVisitors = $oldData ?? collect();
    @endphp

    <div class="space-y-6">
        

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($serviceCards as $card)
                @php
                    $badgeClass = match ($card['tone']) {
                        'warning' => 'visitx-stat-badge visitx-stat-badge-amber',
                        'success' => 'visitx-stat-badge visitx-stat-badge-green',
                        'slate' => 'visitx-stat-badge visitx-stat-badge-slate',
                        default => 'visitx-stat-badge',
                    };
                    $bars = [];
                    $base = max(12, (int) round(($card['value'] / $maxCardValue) * 72));
                    for ($i = 0; $i < 5; $i++) {
                        $bars[] = max(10, min(74, $base - 18 + ($i * 8)));
                    }
                @endphp
                <div class="stat-card visitx-stat-card">
                    <div class="{{ $badgeClass }}">{{ $card['tag'] }}</div>
                    <div class="w-full">
                        <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                        <div class="mt-4 flex items-end justify-between gap-4">
                            <p class="text-4xl font-semibold leading-none text-slate-900">{{ $card['value'] }}</p>
                            <div class="flex h-14 items-end gap-1">
                                @foreach($bars as $barHeight)
                                    <span class="w-2 rounded-full {{ $card['tone'] === 'warning' ? 'bg-amber-400' : ($card['tone'] === 'success' ? 'bg-emerald-400' : ($card['tone'] === 'slate' ? 'bg-slate-300' : 'bg-violet-400')) }}" style="height: {{ $barHeight }}%"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                        <h2 class="text-xl font-semibold text-slate-900">Liste d'attente</h2>
                        <p class="text-sm text-slate-500">Visiteurs du jour pour le service, avec affectation, lancement et terminaison.</p>
                </div>
    
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
                                <th>OBJET</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-right">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 0; @endphp
                            @foreach($data as $row)
                                @php $i++; @endphp
                                <tr>
                                    <td class="px-5 py-4 text-center text-sm font-medium text-slate-700">{{ $i }}</td>
                                    <td class="px-5 py-4 visitx-badge-col"><span class="visitx-badge-pill visitx-badge-pill-soft">{{ $row->badge_n ?: '-' }}</span></td>
                                    <td class="px-5 py-4"><div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div><div class="text-sm text-slate-500">{{ $row->org_name }}</div></td>
                                    <td class="px-5 py-4"><div class="text-sm font-medium text-slate-900">{{ $row->emp_visited }}</div><div class="text-sm text-slate-500">{{ $row->service_name }}</div></td>
                                    <td class="px-5 py-4 text-sm text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-600">{{ $row->subject }}</td>
                                    <td class="px-5 py-4 text-center">
                                        @switch($row->status)
                                            @case(0)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">En attente</span>@break
                                            @case(1)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">En cours</span>@break
                                            @case(2)<span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Terminee</span>@break
                                            @case(3)<span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">Badge a recuperer</span>@break
                                        @endswitch
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end">
                                            <div class="mr-2 flex items-center gap-2">
                                                <a class="service-details-open rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" href="{{ route('i_info', $row->id) }}" title="Details complets" data-details-url="{{ route('i_info', $row->id) }}?embed=1">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                                </a>
                                                <a class="{{ (int) Auth::guard('web')->user()->profile === 4 && !($headServiceIds ?? collect())->contains((int) $row->service_id) ? 'hidden' : '' }} rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" href="{{ route('i_edit_visitors', $row->id) }}" title="Modifier">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m19.3 8.925l-4.25-4.2l1.4-1.4q.575-.575 1.413-.575t1.412.575l1.4 1.4q.575.575.6 1.388t-.55 1.387zM17.85 10.4L7.25 21H3v-4.25l10.6-10.6z"/></svg>
                                                </a>
                                            </div>
                                            @if((int) Auth::guard('web')->user()->profile === 9 && $row->status == 0)
                                                <form action="{{ route('p_edit_visitors', $row->id) }}" method="post" class="flex items-center gap-2">
                                                    @csrf
                                                    <select name="hostname" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                                                        <option value="">Choisir un agent</option>
                                                        @foreach(($serviceUsers ?? collect()) as $user)
                                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="status" value="1">
                                                    <button class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700" type="submit">Affecter et lancer</button>
                                                </form>
                                            @elseif(in_array((int) Auth::guard('web')->user()->profile, [3, 4], true) && empty($row->emp_visited) && ((int) Auth::guard('web')->user()->profile !== 4 || ($headServiceIds ?? collect())->contains((int) $row->service_id)))
                                                <a class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700" href="{{ route('i_edit_visitors', $row->id) }}">
                                                    Affecter
                                                </a>
                                            @elseif($row->status == 0)
                                                <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="status" value="1">
                                                    <button class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700" type="submit">Lancer</button>
                                                </form>
                                            @elseif($row->status == 1)
                                                <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="status" value="3">
                                                    <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700" type="submit">Cloturer</button>
                                                </form>
                                            @elseif($row->status == 3)
                                                <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="status" value="2">
                                                    <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700" type="submit">Cloturer</button>
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
                    <p class="font-semibold text-slate-700">Aucune visite en attente</p>
                    <p class="mt-1 text-sm text-slate-500">Les nouvelles affectations apparaitront ici.</p>
                </div>
            @endif
        </section>

        @if($isFiscalAgent)
            <section class="panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Anciennes donnees DFC</h2>
                        <p class="text-sm text-slate-500">Visites anterieures en attente de suivi ou de cloture.</p>
                    </div>
                </div>

                @if(!$oldVisitors->isEmpty())
                    <div class="overflow-x-auto">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th class="text-center">NUM</th>
                                    <th class="visitx-badge-col">BADGE</th>
                                    <th>VISITEUR</th>
                                    <th>HOTE</th>
                                    <th>DATE ENTREE</th>
                                    <th>OBJET</th>
                                    <th class="text-center">STATUS</th>
                                    <th class="text-right">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 0; @endphp
                                @foreach($oldVisitors as $row)
                                    @php $i++; @endphp
                                    <tr>
                                        <td class="px-5 py-4 text-center text-sm font-medium text-slate-700">{{ $i }}</td>
                                        <td class="px-5 py-4 visitx-badge-col"><span class="visitx-badge-pill visitx-badge-pill-soft">{{ $row->badge_n ?: '-' }}</span></td>
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                            <div class="text-sm text-slate-500">{{ $row->org_name }}</div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="text-sm font-medium text-slate-900">{{ $row->emp_visited }}</div>
                                            <div class="text-sm text-slate-500">{{ $row->service_name }}</div>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-600">{{ $row->subject }}</td>
                                        <td class="px-5 py-4 text-center">
                                            @switch($row->status)
                                                @case(0)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">En attente</span>@break
                                                @case(1)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">En cours</span>@break
                                                @case(2)<span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Terminee</span>@break
                                                @case(3)<span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">Badge a recuperer</span>@break
                                            @endswitch
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <a class="service-details-open rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" href="{{ route('i_info', $row->id) }}" title="Details complets" data-details-url="{{ route('i_info', $row->id) }}?embed=1">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                                </a>
                                                <a class="{{ (int) Auth::guard('web')->user()->profile === 4 && !($headServiceIds ?? collect())->contains((int) $row->service_id) ? 'hidden' : '' }} rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" href="{{ route('i_edit_visitors', $row->id) }}" title="Modifier">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m19.3 8.925l-4.25-4.2l1.4-1.4q.575-.575 1.413-.575t1.412.575l1.4 1.4q.575.575.6 1.388t-.55 1.387zM17.85 10.4L7.25 21H3v-4.25l10.6-10.6z"/></svg>
                                                </a>
                                                @if(in_array((int) $row->status, [0, 1], true))
                                                    <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="status" value="{{ $row->status == 0 ? 1 : 3 }}">
                                                        <button class="rounded-xl {{ $row->status == 0 ? 'bg-violet-600 hover:bg-violet-700' : 'bg-emerald-600 hover:bg-emerald-700' }} px-3 py-2 text-xs font-semibold text-white transition" type="submit">
                                                            {{ $row->status == 0 ? 'Lancer' : 'Cloturer' }}
                                                        </button>
                                                    </form>
                                                @elseif($row->status == 3)
                                                    <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="status" value="2">
                                                        <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700" type="submit">Cloturer</button>
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
                        <p class="font-semibold text-slate-700">Aucune ancienne visite DFC</p>
                        <p class="mt-1 text-sm text-slate-500">Les anciens dossiers apparaîtront ici pour le suivi.</p>
                    </div>
                @endif
            </section>
        @endif
    </div>

    <div id="service-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4" aria-hidden="true">
        <div class="relative flex w-full max-w-none flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="width: 66vw; height: 64vh;" role="dialog" aria-modal="true" aria-labelledby="service-details-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 id="service-details-title" class="text-lg font-semibold text-slate-900">Details complets de la visite</h2>
                <button type="button" data-service-details-close class="rounded-lg border border-slate-200 px-3 py-1 text-2xl leading-none text-slate-600 hover:bg-slate-100" aria-label="Fermer">&times;</button>
            </div>
            <iframe id="service-details-frame" class="min-h-0 flex-1 w-full border-0" title="Details complets de la visite"></iframe>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('service-details-modal');
            const frame = document.getElementById('service-details-frame');
            if (!modal || !frame) return;

            const close = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                frame.src = 'about:blank';
            };

            document.querySelectorAll('[data-details-url]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    frame.src = link.dataset.detailsUrl;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    modal.setAttribute('aria-hidden', 'false');
                });
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            modal.querySelector('[data-service-details-close]').addEventListener('click', close);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
            });
        })();
    </script>
@endsection

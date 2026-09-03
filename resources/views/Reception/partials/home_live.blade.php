<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('home', ['status' => 'all']) }}" class="visitx-stat-card block {{ ($statusFilter ?? 'all') === 'all' ? 'ring-2 ring-indigo-300' : '' }}">
        <div class="visitx-stat-figure visitx-stat-figure-purple">
            <svg class="h-8 w-8" viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3s-3 1.34-3 3s1.34 3 3 3m-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5S5 6.34 5 8s1.34 3 3 3m0 2c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4m8.95 0c-.34 0-.73.02-1.15.05c1.16.84 1.95 1.96 1.95 3.45v3H24v-3c0-2.66-5.33-4-8.05-4m3.05-3a3 3 0 1 0 0-6a3 3 0 0 0 0 6"/></svg>
        </div>
        <div class="visitx-stat-copy">
            <div class="visitx-stat-badge">Aujourd'hui</div>
            <p class="visitx-stat-number">{{ $today }}</p>
            <p class="visitx-stat-label">Visiteurs aujourd'hui</p>
        </div>
    </a>
    <a href="{{ route('home', ['status' => '0']) }}" class="visitx-stat-card block {{ ($statusFilter ?? 'all') === '0' ? 'ring-2 ring-indigo-300' : '' }}">
        <div class="visitx-stat-figure visitx-stat-figure-amber">
            <svg class="h-8 w-8" viewBox="0 0 24 24"><path fill="currentColor" d="M11 17h2v2h-2zm0-8h2v6h-2zm.99-7C6.47 2 2 6.48 2 12s4.47 10 9.99 10S22 17.52 22 12S17.52 2 11.99 2M12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8s8 3.58 8 8s-3.58 8-8 8"/></svg>
        </div>
        <div class="visitx-stat-copy">
            <div class="visitx-stat-badge visitx-stat-badge-amber">Attention</div>
            <p class="visitx-stat-number">{{ $waiting }}</p>
            <p class="visitx-stat-label">En attente</p>
        </div>
    </a>
    <a href="{{ route('home', ['status' => '1']) }}" class="visitx-stat-card block {{ ($statusFilter ?? 'all') === '1' ? 'ring-2 ring-indigo-300' : '' }}">
        <div class="visitx-stat-figure visitx-stat-figure-green">
            <svg class="h-8 w-8" viewBox="0 0 24 24"><path fill="currentColor" d="m13 5l7 7l-7 7v-4H3v-6h10zm-1-3v4H4v2h8v4l5-5z"/></svg>
        </div>
        <div class="visitx-stat-copy">
            <div class="visitx-stat-badge visitx-stat-badge-green">En cours</div>
            <p class="visitx-stat-number">{{ $progress }}</p>
            <p class="visitx-stat-label">En cours</p>
        </div>
    </a>
    <a href="{{ route('home', ['status' => '2']) }}" class="visitx-stat-card block {{ ($statusFilter ?? 'all') === '2' ? 'ring-2 ring-indigo-300' : '' }}">
        <div class="visitx-stat-figure visitx-stat-figure-slate">
            <svg class="h-8 w-8" viewBox="0 0 24 24"><path fill="currentColor" d="m9 16.17l-3.88-3.88L4 13.41l5 5L20 7.41L18.59 6z"/></svg>
        </div>
        <div class="visitx-stat-copy">
            <div class="visitx-stat-badge visitx-stat-badge-slate">Cloturees</div>
            <p class="visitx-stat-number">{{ $finished }}</p>
            <p class="visitx-stat-label">Terminees</p>
        </div>
    </a>
</div>

<section class="panel overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="visitx-eyebrow">Tableau de bord</p>
            
            <p class="text-sm text-slate-500">Visiteurs en attente de traitement.</p>
        </div>
        <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $data->count() }} ligne(s)</span>
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
                        <th class="text-center">STATUS</th>
                        @if(in_array((int) Auth::guard('web')->user()->profile, [5, 8], true))
                            <th class="visitx-actions-col text-right">ACTION</th>
                        @endif
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
                                <div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                <div class="text-sm text-slate-500">{{ $row->org_name }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-medium text-slate-900">{{ $row->emp_visited }}</div>
                                <div class="text-sm text-slate-500">{{ $row->service_name }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                            <td class="px-5 py-4 text-center">
                                @switch($row->status)
                                    @case(0)<span class="visitx-status-pill visitx-status-pill-amber">En attente</span>@break
                                    @case(1)<span class="visitx-status-pill visitx-status-pill-green">En cours</span>@break
                                    @case(2)<span class="visitx-status-pill visitx-status-pill-slate">Terminee</span>@break
                                    @case(3)<span class="visitx-status-pill visitx-status-pill-blue">Badge a recuperer</span>@break
                                @endswitch
                            </td>
                            @if(in_array((int) Auth::guard('web')->user()->profile, [5, 8], true))
                                <td class="visitx-actions-col px-5 py-4 text-right">
                                    <div class="visitx-action-stack">
                                        @if((int) Auth::guard('web')->user()->profile === 5 && ($row->workflow_type ?? 'classic') === 'bog' && (int) $row->status !== 2)
                                            <form action="{{ route('p_workflow_visitors', $row->id) }}" method="post">
                                                @csrf
                                                <input type="hidden" name="status" value="2">
                                                <button type="submit" class="visitx-table-action visitx-table-action-exit">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M10 17l5-5l-5-5v3H3v4h7zm11-5a9 9 0 1 1-18 0a9 9 0 0 1 18 0"/></svg>
                                                    Sortie
                                                </button>
                                            </form>
                                        @endif
                                        <a class="visitx-table-action" href="{{ route('i_info',$row->id) }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 5c-7 0-10 7-10 7s3 7 10 7s10-7 10-7s-3-7-10-7m0 11a4 4 0 1 1 0-8a4 4 0 0 1 0 8"/></svg>
                                            Voir
                                        </a>
                                        @if(in_array((int) Auth::guard('web')->user()->profile, [5, 8], true))
                                            <a class="visitx-table-action visitx-table-action-edit" href="{{ route('i_edit_visitors',$row->id) }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="m5 17.59l3.79-.66L19.44 6.28a1.25 1.25 0 0 0 0-1.77L18.5 3.57a1.25 1.25 0 0 0-1.77 0L6.37 13.93zm14.71-13.3l-.99.99l-1.99-1.99l.99-.99a1 1 0 0 1 1.41 0l.58.58a1 1 0 0 1 0 1.41"/></svg>
                                                Modifier
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-5 py-12 text-center">
            <p class="font-semibold text-slate-700">Aucune visite en attente</p>
            <p class="mt-1 text-sm text-slate-500">Les nouvelles visites apparaitront ici.</p>
        </div>
    @endif
</section>

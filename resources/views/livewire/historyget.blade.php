<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card visitx-stat-card">
            <div class="visitx-stat-badge">Global</div>
            <div class="w-full">
                <p class="text-sm text-slate-500">Visites totales</p>
                <div class="mt-4 flex items-end justify-between gap-4">
                    <p class="text-4xl font-semibold leading-none text-slate-900">{{ $totalVisits ?? 0 }}</p>
                    <div class="flex h-14 items-end gap-1">
                        @foreach([26, 34, 30, 42, 48] as $bar)
                            <span class="w-2 rounded-full bg-violet-400" style="height: {{ $bar }}%"></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card visitx-stat-card">
            <div class="visitx-stat-badge visitx-stat-badge-green">Jour</div>
            <div class="w-full">
                <p class="text-sm text-slate-500">Aujourd'hui</p>
                <div class="mt-4 flex items-end justify-between gap-4">
                    <p class="text-4xl font-semibold leading-none text-slate-900">{{ $todayVisits ?? 0 }}</p>
                    <div class="flex h-14 items-end gap-1">
                        @foreach([18, 22, 28, 36, 44] as $bar)
                            <span class="w-2 rounded-full bg-emerald-400" style="height: {{ $bar }}%"></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card visitx-stat-card">
            <div class="visitx-stat-badge visitx-stat-badge-amber">Societes</div>
            <div class="w-full">
                <p class="text-sm text-slate-500">Societes</p>
                <div class="mt-4 flex items-end justify-between gap-4">
                    <p class="text-4xl font-semibold leading-none text-slate-900">{{ $companiesCount ?? 0 }}</p>
                    <div class="flex h-14 items-end gap-1">
                        @foreach([20, 26, 38, 32, 40] as $bar)
                            <span class="w-2 rounded-full bg-amber-400" style="height: {{ $bar }}%"></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card visitx-stat-card">
            <div class="visitx-stat-badge visitx-stat-badge-slate">Presence</div>
            <div class="w-full">
                <p class="text-sm text-slate-500">Presents</p>
                <div class="mt-4 flex items-end justify-between gap-4">
                    <p class="text-4xl font-semibold leading-none text-slate-900">{{ $activeVisitors ?? 0 }}</p>
                    <div class="flex h-14 items-end gap-1">
                        @foreach([12, 18, 22, 26, 30] as $bar)
                            <span class="w-2 rounded-full bg-slate-300" style="height: {{ $bar }}%"></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="panel panel-pad">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="visitx-eyebrow">Recherche historique</p>
                <h2 class="text-xl font-semibold text-slate-900">Filtres de consultation</h2>
                <p class="mt-1 text-sm text-slate-500">Recherchez par nom, societe, permis minier, numero de badge ou date.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="exportExcel" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">
                    Exporter Excel
                </button>
                <button type="button" wire:click="exportPDF" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">
                    Exporter PDF
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-[220px_minmax(0,1fr)_220px_140px]">
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Recherche par</label>
                <select wire:model="cat" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100">
                    <option value="1">Nom et Prenom</option>
                    <option value="2">Societe</option>
                    <option value="4">Permis minier</option>
                    <option value="5">NUMERO BADGE</option>
                    <option value="3">Date</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">{{ $cat == '3' ? 'Date' : 'Recherche' }}</label>
                <input type="date" wire:key="history-date-filter" class="{{ $cat == '3' ? 'block' : 'hidden' }} w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100" wire:model="date" wire:change="search">
                <input type="text" wire:key="history-text-filter" placeholder="Rechercher..." class="{{ $cat == '3' ? 'hidden' : 'block' }} w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100" wire:model.debounce.500ms="query" wire:keydown.enter.prevent="search">
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Statut</label>
                <select wire:model="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100">
                    <option value="">Tous les statuts</option>
                    <option value="0">En attente</option>
                    <option value="1">En cours</option>
                    <option value="2">Terminee</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="search" class="w-full rounded-2xl bg-[#7F56D9] px-4 py-3 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(127,86,217,0.22)] transition hover:bg-[#6941C6]">
                    Rechercher
                </button>
            </div>
        </div>
    </section>

    @if(!empty($results) && $results->isNotEmpty())
        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Resultats</h2>
                    <p class="text-sm text-slate-500">{{ $results->count() }} visite(s) trouvee(s).</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="modern-table" aria-label="Liste des visites">
                    <thead>
                        <tr>
                            <th class="w-44">NUMERO BADGE</th>
                            <th>Visiteur</th>
                            <th>Hote</th>
                            <th>Date d'entree</th>
                            <th>Statut</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="visitx-badge-pill visitx-badge-pill-soft w-fit">{{ $row->badge_n ?: '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-sm font-bold text-violet-700">
                                            {{ strtoupper(substr($row->firstname ?? 'V', 0, 1)) }}{{ strtoupper(substr($row->lastname ?? 'I', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                            <div class="text-sm text-slate-500">{{ $row->org_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-medium text-slate-900">{{ $row->emp_visited }}</div>
                                    <div class="text-sm text-slate-500">{{ $row->service_name ?? $row->ant_name ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                                <td class="px-5 py-4">
                                    @switch($row->status)
                                        @case(0)
                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">En attente</span>
                                            @break
                                        @case(1)
                                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">En cours</span>
                                            @break
                                        @case(2)
                                            <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Terminee</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        <a href="{{ route('i_info', $row->id) }}" class="rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700" title="Details complets">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @elseif($noresults)
        <section class="panel">
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-slate-700">Aucun resultat</p>
                <p class="mt-1 text-sm text-slate-500">Aucune visite ne correspond aux filtres saisis.</p>
            </div>
        </section>
    @endif
</div>


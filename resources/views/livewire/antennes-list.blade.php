<div class="space-y-5">
    @php
        $totalPassages = 0;
        $activeAntennes = 0;
        foreach ($antennes as $antenneStat) {
            foreach ($antennes_visited as $visitedStat) {
                if ($antenneStat->ant_id == $visitedStat->ant_location) {
                    $totalPassages += $visitedStat->count;
                    if ($visitedStat->count > 0) {
                        $activeAntennes++;
                    }
                    break;
                }
            }
        }
    @endphp
<section class="overflow-hidden rounded-3xl bg-white shadow-sm">
               <div class="grid gap-5 p-5 lg:grid-cols-[1.7fr_320px]">
    <div class="relative mx-auto w-full max-w-[80%]" style="aspect-ratio: 1076 / 992; min-height: 650px;">
             
            <img src="{{  url('images/algeria-map-reference.png') }}" alt="Carte d'Algérie" class="absolute inset-0 z-0 block h-full w-full object-cover" style="transform: scale(1); transform-origin: center center;">
                <div class="absolute inset-0 z-10">
                    
                    @foreach($mapAntennes as $marker)
                        @php
                            $isSelected = (int) ($selectedAntenneId ?? 0) === (int) $marker['id'];
                        @endphp
                        <button
                            type="button"
                            wire:click="select('{{ $marker['id'] }}')"
                            class="group absolute cursor-pointer outline-none transition"
                            style="left: {{ $marker['x'] }}%; top: {{ $marker['y'] }}%; width: {{ $marker['w'] ?? 10 }}%; height: {{ $marker['h'] ?? 10 }}%; transform: translate(-50%, -50%);"
                            title="{{ $marker['name'] }}"
                            aria-label="{{ $marker['name'] }}"
                        >
                            <span class="absolute inset-0 rounded-[999px] border border-transparent bg-transparent transition group-hover:border-violet-400/70 group-hover:bg-violet-200/10 {{ $isSelected ? 'border-violet-500/70 bg-violet-200/15 ring-4 ring-violet-300/70' : '' }}"></span>
                            <span class="sr-only">{{ $marker['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
  
        </div>
    </section>

    
    @if ($state)
        <div x-data="{ modelOpen: true }">
            <div x-show="modelOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-end justify-center px-4 text-center md:items-center sm:p-0">
                    <div x-cloak wire:click="close()" x-show="modelOpen" x-transition.opacity class="fixed inset-0 bg-slate-400/40 backdrop-blur-sm" aria-hidden="true"></div>

                    <div x-cloak x-show="modelOpen" x-transition class="relative my-8 inline-block w-full max-w-6xl overflow-hidden rounded-2xl border border-slate-200 bg-white text-left align-middle shadow-2xl">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 text-slate-950" style="background:rgba(41,73,166,.08)">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest" style="color:#2949A6">Detail antenne</p>
                                <h1 class="mt-1 text-xl font-black uppercase tracking-tight">Antenne {{ $antenne_n->antenne_name }}</h1>
                                <p class="mt-1 text-xs font-medium text-slate-500">Visiteurs enregistres aujourd'hui.</p>
                            </div>

                            <button wire:click="close()" class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 transition hover:bg-indigo-100 hover:text-indigo-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="m12 10.6l4.95-4.95l1.4 1.4L13.4 12l4.95 4.95l-1.4 1.4L12 13.4l-4.95 4.95l-1.4-1.4L10.6 12L5.65 7.05l1.4-1.4z"/></svg>
                            </button>
                        </div>

                        @if (!$info->isEmpty())
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
                                        @php $i = 0; @endphp
                                        @foreach($info as $row)
                                            @php $i++; @endphp
                                            <tr>
                                                <td class="px-5 py-4 text-sm font-bold text-slate-700">{{ $i }}</td>
                                                <td class="px-5 py-4">
                                                    <div class="font-bold text-slate-900">{{ $row->firstname }} {{ $row->lastname }}</div>
                                                    <div class="text-xs font-medium text-slate-500">{{ $row->org_name }}</div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="text-sm font-bold text-slate-900">{{ $row->emp_visited }}</div>
                                                    <div class="text-xs font-medium text-slate-500">Antenne {{ $row->service_name }}</div>
                                                </td>
                                                <td class="px-5 py-4 text-sm font-medium text-slate-500">{{ to_normal_date($row->entry_date) }}</td>
                                                <td class="px-5 py-4">
                                                    @switch($row->status)
                                                        @case(0)<span class="status-chip status-waiting">En attente</span>@break
                                                        @case(1)<span class="status-chip status-progress">En cours</span>@break
                                                        @case(2)<span class="status-chip status-done">Terminee</span>@break
                                                    @endswitch
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="flex justify-end">
                                                        <a class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700" href="{{ route('i_ant_p_info',$row->id) }}" title="Details complets">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5m0 12.5a5 5 0 1 1 0-10a5 5 0 0 1 0 10m0-8a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="px-6 py-14 text-center">
                                <p class="font-black text-slate-700">Aucun visiteur aujourd'hui</p>
                                <p class="mt-1 text-sm text-slate-500">Cette antenne n'a aucun passage enregistre pour la journee.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

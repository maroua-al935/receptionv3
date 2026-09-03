@extends((int) Auth::guard('web')->user()->profile === 4 ? 'Service.layouts.master' : 'Reception.layouts.master')

@section('body')
@php
    $profile = (int) Auth::guard('web')->user()->profile;
    $isBadgeAgent = $profile === 5 || Auth::guard('web')->user()->name === 'agent_saisie_badge' || Auth::guard('web')->user()->email === 'agent_saisie_badge@visilog.local';
@endphp
<form action="{{ route('p_edit_visitors', preg_replace('/guests\/edit\//', '', Request::path())) }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="w-full max-w-6xl">
        <div class="form-card">
            <div class="border-b border-slate-100 pb-5">
                <span class="page-title block">{{ $profile === 8 ? 'Orienter la visite' : 'Modifier la visite' }}</span>
                <p class="page-subtitle">Orientation, affectation service et cloture apres restitution du badge.</p>
            </div>

            @if($isBadgeAgent)
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-700">Visiteur</span>
                    <p class="mt-1 text-slate-900">{{ $data[0]->firstname }} {{ $data[0]->lastname }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Numero badge</span>
                    <input type="text" name="badge_n" placeholder="Badge remis" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2" value="{{ $data[0]->badge_n }}" />
                </div>
            </div>
            @elseif(!in_array($profile, [3, 4, 8, 9], true))
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Nom</span>
                    <input type="text" name="fname" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2" value="{{ $data[0]->firstname }}" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Prenom</span>
                    <input type="text" name="lname" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2" value="{{ $data[0]->lastname }}" />
                </label>
            </div>
            @else
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-700">Visiteur</span>
                    <p class="mt-1 text-slate-900">{{ $data[0]->firstname }} {{ $data[0]->lastname }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Numero badge</span>
                    <input type="text" name="badge_n" placeholder="Badge remis" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2" value="{{ $data[0]->badge_n }}" />
                </div>
            </div>
            @endif

            @if(!$isBadgeAgent)
            <div class="mt-5">
                @if($profile === 8)
                    <div class="grid gap-4 rounded-xl bg-slate-50/70 p-4 lg:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Service</span>
                            <select name="service" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                                <option value="">Choisir un service</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}" @selected($data[0]->service_id == $service->id)>{{ $service->group_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Societe</span>
                            <input type="text" name="org" value="{{ $data[0]->org_name }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2" placeholder="Societe optionnelle">
                        </label>
                        <label class="block lg:col-span-2">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Wilaya</span>
                            <span class="mb-1 block text-xs text-slate-500">Maintenez Ctrl pour sélectionner plusieurs wilayas.</span>
                            @php
                                $storedWilayaBeforeFields = $data[0]->wilaya ?? $data[0]->wilaya_name ?? '';
                                $decodedWilayaBeforeFields = is_string($storedWilayaBeforeFields) ? json_decode($storedWilayaBeforeFields, true) : $storedWilayaBeforeFields;
                                $currentWilayaBeforeFields = old('wilaya', is_array($decodedWilayaBeforeFields) ? $decodedWilayaBeforeFields : [$storedWilayaBeforeFields]);
                                $currentWilayaBeforeFields = array_values(array_filter(array_map('strval', (array) $currentWilayaBeforeFields), fn ($value) => $value !== ''));
                            @endphp
                            <div id="wilaya-fields" class="space-y-2" data-selected-wilayas="{{ implode(',', $currentWilayaBeforeFields) }}">
                                <div class="flex items-center gap-2" data-wilaya-row>
                            <select name="wilaya[]" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                                <option value="">Wilaya optionnelle</option>
                                @php
                                    $wilayas = [
                                        '01' => 'Adrar',
                                        '02' => 'Chlef',
                                        '03' => 'Laghouat',
                                        '04' => 'Oum El Bouaghi',
                                        '05' => 'Batna',
                                        '06' => 'Béjaïa',
                                        '07' => 'Biskra',
                                        '08' => 'Béchar',
                                        '09' => 'Blida',
                                        '10' => 'Bouira',
                                        '11' => 'Tamanrasset',
                                        '12' => 'Tébessa',
                                        '13' => 'Tlemcen',
                                        '14' => 'Tiaret',
                                        '15' => 'Tizi Ouzou',
                                        '16' => 'Alger',
                                        '17' => 'Djelfa',
                                        '18' => 'Jijel',
                                        '19' => 'Sétif',
                                        '20' => 'Saïda',
                                        '21' => 'Skikda',
                                        '22' => 'Sidi Bel Abbès',
                                        '23' => 'Annaba',
                                        '24' => 'Guelma',
                                        '25' => 'Constantine',
                                        '26' => 'Médéa',
                                        '27' => 'Mostaganem',
                                        '28' => 'M\'Sila',
                                        '29' => 'Mascara',
                                        '30' => 'Ouargla',
                                        '31' => 'Oran',
                                        '32' => 'El Bayadh',
                                        '33' => 'Illizi',
                                        '34' => 'Bordj Bou Arreridj',
                                        '35' => 'Boumerdès',
                                        '36' => 'El Tarf',
                                        '37' => 'Tindouf',
                                        '38' => 'Tissemsilt',
                                        '39' => 'El Oued',
                                        '40' => 'Khenchela',
                                        '41' => 'Souk Ahras',
                                        '42' => 'Tipaza',
                                        '43' => 'Mila',
                                        '44' => 'Aïn Defla',
                                        '45' => 'Naâma',
                                        '46' => 'Aïn Témouchent',
                                        '47' => 'Ghardaïa',
                                        '48' => 'Relizane',
                                        '49' => 'El M\'Ghair',
                                        '50' => 'El Meniaa',
                                        '51' => 'Ouled Djellal',
                                        '52' => 'Bordj Badji Mokhtar',
                                        '53' => 'Béni Abbès',
                                        '54' => 'Timimoun',
                                        '55' => 'Touggourt',
                                        '56' => 'Djanet',
                                        '57' => 'In Salah',
                                        '58' => 'In Guezzam',
                                        '59' => 'Aflou',
                                        '60' => 'Barika',
                                        '61' => 'El Kantara',
                                        '62' => 'Bir El Ater',
                                        '63' => 'El Aricha',
                                        '64' => 'Ksar Chellala',
                                        '65' => 'Aïn Oussara',
                                        '66' => 'Messaad',
                                        '67' => 'Ksar El Boukhari',
                                        '68' => 'Bou Saâda',
                                        '69' => 'El Abiodh Sidi Cheikh',
                                    ];
                                    $storedWilaya = $data[0]->wilaya ?? $data[0]->wilaya_name ?? '';
                                    $decodedWilaya = is_string($storedWilaya) ? json_decode($storedWilaya, true) : $storedWilaya;
                                    $currentWilaya = old('wilaya', is_array($decodedWilaya) ? $decodedWilaya : [$storedWilaya]);
                                    $currentWilaya = array_values(array_filter(array_map('strval', (array) $currentWilaya), fn ($value) => $value !== ''));
                                @endphp
                                @foreach($wilayas as $code => $name)
                                    <option value="{{ $code }}" @selected(in_array((string) $code, $currentWilaya, true) || in_array($name, $currentWilaya, true))>{{ $code }} : {{ $name }}</option>
                                @endforeach
                            </select>
                                    <button type="button" data-wilaya-remove class="hidden rounded-lg border border-red-200 px-3 py-2 text-red-600 hover:bg-red-50" aria-label="Supprimer cette wilaya">&times;</button>
                                </div>
                            </div>
                            <button type="button" id="wilaya-add" class="mt-2 rounded-lg border border-violet-200 px-3 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-50">+ Ajouter une wilaya</button>
                            <script>
                                (() => {
                                    const fields = document.getElementById('wilaya-fields');
                                    const add = document.getElementById('wilaya-add');
                                    if (!fields || !add) return;
                                    const refresh = () => fields.querySelectorAll('[data-wilaya-remove]').forEach((button) => button.classList.toggle('hidden', fields.children.length < 2));
                                    const selected = (fields.dataset.selectedWilayas || '').split(',').filter(Boolean);
                                    if (selected.length > 0) {
                                        fields.firstElementChild.querySelector('select').value = selected[0];
                                        selected.slice(1).forEach((value) => {
                                            const row = fields.firstElementChild.cloneNode(true);
                                            row.querySelector('select').value = value;
                                            row.querySelector('[data-wilaya-remove]').classList.remove('hidden');
                                            fields.appendChild(row);
                                        });
                                    }
                                    add.addEventListener('click', () => {
                                        const row = fields.firstElementChild.cloneNode(true);
                                        row.querySelector('select').selectedIndex = 0;
                                        row.querySelector('[data-wilaya-remove]').classList.remove('hidden');
                                        fields.appendChild(row);
                                        refresh();
                                    });
                                    fields.addEventListener('click', (event) => {
                                        const button = event.target.closest('[data-wilaya-remove]');
                                        if (button) { button.closest('[data-wilaya-row]').remove(); refresh(); }
                                    });
                                    refresh();
                                })();
                            </script>
                        </label>
                    </div>
                @elseif(in_array($profile, [3, 4, 9], true))
                    <div class="rounded-xl bg-slate-50/70 p-4">
                        <div class="mb-4">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Service</span>
                            <p class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-slate-900">{{ $data[0]->service_name }}</p>
                        </div>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Personne visitee</span>
                            <select name="hostname" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                                <option value="">Choisir un employe</option>
                                @foreach(($serviceUsers ?? collect()) as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                @else
                    @livewire('ddnames', ['service_s' => $data[0]->service_id, 'name_s' => $data[0]->emp_visited])
                @endif
            </div>
            @endif

            @if(!in_array($profile, [5, 8], true) && !$isBadgeAgent)
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Categorie</span>
                    <select name="category" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                        @foreach($cats as $cat)
                            <option value="{{ $cat->id }}" @selected($data[0]->category == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Societe</span>
                    <input type="text" name="org" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2" value="{{ $data[0]->org_name }}">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Piece d'identite</span>
                    <select name="alt_type" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                        @foreach ($id_types as $type)
                            <option value="{{ $type->id }}" @selected($type->id == $data[0]->id_type)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Statut</span>
                    <select id="state" name="status" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                        <option value="0" @selected($data[0]->status == 0)>En attente</option>
                        <option value="1" @selected($data[0]->status == 1)>En cours</option>
                        <option value="3" @selected($data[0]->status == 3)>Visite terminee - badge a recuperer</option>
                        <option value="2" @selected($data[0]->status == 2)>Cloturee</option>
                    </select>
                </label>

                <label id="exittime" class="hidden">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Date de sortie</span>
                    <input type="datetime-local" id="exittime_val" name="exitdate" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
                </label>
            </div>
            @endif

            <div class="mt-6 flex justify-end">
                <input type="submit" class="primary-action w-28 button" value="{{ $profile === 8 ? 'Orienter' : (in_array($profile, [3, 4, 9], true) ? 'Affecter' : 'Modifier') }}">
            </div>
        </div>
    </div>
</form>

<script type="module">
    $("#state").on('change click', function() {
        var date = new Date();
        var dateStr =
            date.getFullYear() +
            "-" +
            ("00" + (date.getMonth() + 1)).slice(-2) +
            "-" +
            ("00" + date.getDate()).slice(-2) +
            "T" +
            ("00" + date.getHours()).slice(-2) + ":" +
            ("00" + date.getMinutes()).slice(-2);
        if ($("#state").find(":selected").val() == "2") {
            $("#exittime_val").val(dateStr);
            $("#exittime").removeClass('hidden');
        } else {
            $("#exittime").addClass('hidden');
        }
    });
</script>
@endsection

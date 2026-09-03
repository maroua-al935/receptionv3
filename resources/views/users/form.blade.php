@extends('President.layouts.master')

@section('body')
    @php
        $selectedServiceIds = array_map('intval', old('services', isset($selectedGroups) ? $selectedGroups->all() : []));
        $headServiceIds = array_map('intval', old('head_services', isset($headGroups) ? $headGroups->all() : []));
        $selectedAntenneIds = array_map('intval', old('antennes', isset($selectedAntennes) ? $selectedAntennes->all() : []));
        $headAntenneIds = array_map('intval', old('head_antennes', isset($headAntennes) ? $headAntennes->all() : []));
        $assignmentType = old('assignment_type', !empty($selectedAntenneIds) ? 'antenne' : 'siege');
        $isEdit = isset($user);
    @endphp

    <div class="space-y-6">
        <div class="visitx-hero">
            <div>
                <p class="visitx-eyebrow">Administration</p>
                <h1 class="page-title">{{ $isEdit ? 'Modifier un utilisateur' : 'Ajouter un utilisateur' }}</h1>
                <p class="page-subtitle">Configuration du compte, du profil et des affectations.</p>
            </div>
            <div class="visitx-hero-side">
                <div class="visitx-hero-chip">
                    <span class="visitx-hero-dot"></span>
                    {{ $isEdit ? 'Edition en cours' : 'Creation en cours' }}
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-bold">Corrigez les informations suivantes :</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $isEdit ? route('users.update', $user->id) : route('users.store') }}" method="POST" class="space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="panel panel-pad">
                

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                   
                    <div>
                        <label for="profile" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Profil *</label>
                        <select name="profile" id="profile" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100" required>
                            @foreach ($profiles as $profile)
                                <option value="{{ $profile->id }}" {{ (int) old('profile', $user->profile ?? 0) === (int) $profile->id ? 'selected' : '' }}>
                                    {{ $profile->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="firstname" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Prenom *</label>
                        <input type="text" name="firstname" id="firstname" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100" value="{{ old('firstname', $user->firstname ?? '') }}" required>
                    </div>
                    <div>
                        <label for="lastname" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Nom *</label>
                        <input type="text" name="lastname" id="lastname" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-4 focus:ring-violet-100" value="{{ old('lastname', $user->lastname ?? '') }}" required>
                    </div>
                   
                </div>
            </section>

            <section class="panel panel-pad">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-900">Type d'affectation</h2>
                    <p class="text-sm text-slate-500">Choisissez si l'utilisateur depend du siege ou d'une antenne.</p>
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                        <input type="radio" name="assignment_type" value="siege" class="assignment-type h-4 w-4" @checked($assignmentType === 'siege')>
                        Siege
                    </label>
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                        <input type="radio" name="assignment_type" value="antenne" class="assignment-type h-4 w-4" @checked($assignmentType === 'antenne')>
                        Antenne
                    </label>
                </div>
            </section>

            <section id="siege-panel" class="panel panel-pad">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-900">Services et chef de service</h2>
                    <p class="text-sm text-slate-500">Cochez les services associes. Cochez "Chef" pour definir le chef du service.</p>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th class="text-center">Membre</th>
                                <th class="text-center">Chef</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $group)
                                @php
                                    $isSelected = in_array((int) $group->id, $selectedServiceIds, true);
                                    $isHead = in_array((int) $group->id, $headServiceIds, true);
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-800">{{ $group->group_name }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" name="services[]" value="{{ $group->id }}" class="service-checkbox h-4 w-4 rounded border-slate-300" @checked($isSelected)>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" name="head_services[]" value="{{ $group->id }}" class="head-checkbox h-4 w-4 rounded border-slate-300" @checked($isHead) @disabled(!$isSelected)>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="antenne-panel" class="panel panel-pad">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-900">Antennes et chef d'antenne</h2>
                    <p class="text-sm text-slate-500">Cochez les antennes associees. Cochez "Chef" pour receptionner et orienter les visites de cette antenne.</p>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Antenne</th>
                                <th class="text-center">Membre</th>
                                <th class="text-center">Chef</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($antennes as $antenne)
                                @php
                                    $isSelected = in_array((int) $antenne->id, $selectedAntenneIds, true);
                                    $isHead = in_array((int) $antenne->id, $headAntenneIds, true);
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-800">{{ $antenne->antenne_name }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" name="antennes[]" value="{{ $antenne->id }}" class="antenne-checkbox h-4 w-4 rounded border-slate-300" @checked($isSelected)>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" name="head_antennes[]" value="{{ $antenne->id }}" class="antenne-head-checkbox h-4 w-4 rounded border-slate-300" @checked($isHead) @disabled(!$isSelected)>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                    Retour
                </a>
                <button type="submit" class="rounded-2xl bg-[#7F56D9] px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(127,86,217,0.22)] transition hover:bg-[#6941C6]">
                    {{ $isEdit ? 'Mettre a jour' : 'Creer' }}
                </button>
            </div>
        </form>
    </div>

    <script>
        function syncHeadCheckboxes() {
            document.querySelectorAll('.service-checkbox').forEach(function (serviceCheckbox) {
                const row = serviceCheckbox.closest('tr');
                const headCheckbox = row.querySelector('.head-checkbox');
                headCheckbox.disabled = !serviceCheckbox.checked || serviceCheckbox.disabled;
                if (!serviceCheckbox.checked) {
                    headCheckbox.checked = false;
                }
            });

            document.querySelectorAll('.antenne-checkbox').forEach(function (antenneCheckbox) {
                const row = antenneCheckbox.closest('tr');
                const headCheckbox = row.querySelector('.antenne-head-checkbox');
                headCheckbox.disabled = !antenneCheckbox.checked || antenneCheckbox.disabled;
                if (!antenneCheckbox.checked) {
                    headCheckbox.checked = false;
                }
            });
        }

        function setAssignmentType(type) {
            const siegePanel = document.getElementById('siege-panel');
            const antennePanel = document.getElementById('antenne-panel');
            const showSiege = type === 'siege';

            siegePanel.classList.toggle('hidden', !showSiege);
            antennePanel.classList.toggle('hidden', showSiege);

            siegePanel.querySelectorAll('input').forEach(function (input) {
                input.disabled = !showSiege;
            });
            antennePanel.querySelectorAll('input').forEach(function (input) {
                input.disabled = showSiege;
            });

            syncHeadCheckboxes();
        }

        document.querySelectorAll('.assignment-type').forEach(function (radio) {
            radio.addEventListener('change', function () {
                setAssignmentType(radio.value);
            });
        });

        document.querySelectorAll('.service-checkbox').forEach(function (serviceCheckbox) {
            serviceCheckbox.addEventListener('change', function () {
                syncHeadCheckboxes();
            });
        });

        document.querySelectorAll('.head-checkbox').forEach(function (headCheckbox) {
            headCheckbox.addEventListener('change', function () {
                const row = headCheckbox.closest('tr');
                const serviceCheckbox = row.querySelector('.service-checkbox');
                if (headCheckbox.checked) {
                    serviceCheckbox.checked = true;
                    headCheckbox.disabled = false;
                }
            });
        });

        document.querySelectorAll('.antenne-checkbox').forEach(function (antenneCheckbox) {
            antenneCheckbox.addEventListener('change', function () {
                syncHeadCheckboxes();
            });
        });

        document.querySelectorAll('.antenne-head-checkbox').forEach(function (headCheckbox) {
            headCheckbox.addEventListener('change', function () {
                const row = headCheckbox.closest('tr');
                const antenneCheckbox = row.querySelector('.antenne-checkbox');
                if (headCheckbox.checked) {
                    antenneCheckbox.checked = true;
                    headCheckbox.disabled = false;
                }
            });
        });

        const checkedAssignmentType = document.querySelector('.assignment-type:checked');
        setAssignmentType(checkedAssignmentType ? checkedAssignmentType.value : 'siege');
    </script>
@endsection

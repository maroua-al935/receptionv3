@extends('Reception.layouts.master')

@section('body')
<form action="{{ route('p_add_visitors') }}" method="post" enctype="multipart/form-data" class="visitx-form">
    @csrf
    <input type="hidden" name="new_visitor" value="1">

    <div class="w-full max-w-5xl py-2">
        <div class="form-card">
            <div class="visitx-form-hero">
                <div>
                    <span class="visitx-eyebrow block">Nouveau passage</span>
                    <span class="page-title block">Enregistrer une visite</span>
                    <p class="page-subtitle">Identifier le visiteur et remettre le badge.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button id="elyctis-read-btn" type="button" class="rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Lire la carte</button>
                    <span id="elyctis-status" class="text-sm font-semibold text-gray-500"></span>
                </div>
                @if(!is_null(Session::get('error')))
                    <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="text-sm font-semibold text-red-600">{{ Session::get('error') }}</span>
                @endif
            </div>

            @if($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Impossible d'ajouter la visite. Corrigez les informations manquantes :</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="new_user" class="mt-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-slate-700">Nom <span class="text-red-600">*</span></span>
                        <input type="text" name="fname" required value="{{ old('fname') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-slate-700">Prenom <span class="text-red-600">*</span></span>
                        <input type="text" name="lname" required value="{{ old('lname') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Poste</span>
                    <select name="role" id="role" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                        <option value="other" @selected(old('role') == 'other')>Autre</option>
                    </select>
                </label>

                <label id="other" class="hidden block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Autre Poste</span>
                    <input id="other_field" type="text" name="other_value" value="{{ old('other_value') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Piece d'identite</span>
                    <select id="elyctis-id-cat" name="id_cat" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                        @foreach ($id_types as $type)
                            <option value="{{ $type->id }}" @selected(old('id_cat') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Numero piece <span class="text-red-600">*</span></span>
                    <input type="text" name="cin" required value="{{ old('cin') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">NIN <span class="text-red-600">*</span></span>
                    <input type="text" name="nin" required value="{{ old('nin') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                </label>
            </div>

            <div class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Type</span>
                    <select id="visit-category" name="category" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                        @foreach($cats as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label id="subject-wrapper" class="block">
                    <span id="subject-label" class="mb-1 block text-sm font-semibold text-slate-700">Objet de visite</span>
                    <input id="subject-field" type="text" name="subject" value="{{ old('subject') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Societe</span>
                    <input type="text" name="org" value="{{ old('org') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Heure d'entree</span>
                    <input type="datetime-local" name="date_entry" value="{{ old('date_entry', $cur_date) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Numero badge <span class="text-red-600">*</span></span>
                    <input type="text" name="badge_n" placeholder="Badge remis" required value="{{ old('badge_n') }}" class="w-full rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 font-semibold text-slate-900">
                    @error('badge_n')
                        <span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-slate-700">Permis minier</span>
                        <input id="permetadd" type="text" name="permetadd" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">
                    </label>
                    <button id="addbtn" type="button" class="self-end rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-100">
                        <svg class="h-5 w-5" viewBox="0 0 256 256"><path fill="currentColor" d="M228 128a12 12 0 0 1-12 12h-76v76a12 12 0 0 1-24 0v-76H40a12 12 0 0 1 0-24h76V40a12 12 0 0 1 24 0v76h76a12 12 0 0 1 12 12Z"/></svg>
                    </button>
                    <button id="clrbtn" type="button" class="self-end rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-100">
                        <svg class="h-5 w-5" viewBox="0 0 256 256"><path fill="currentColor" d="M216 48h-40v-8a24 24 0 0 0-24-24h-48a24 24 0 0 0-24 24v8H40a8 8 0 0 0 0 16h8v144a16 16 0 0 0 16 16h128a16 16 0 0 0 16-16V64h8a8 8 0 0 0 0-16ZM96 40a8 8 0 0 1 8-8h48a8 8 0 0 1 8 8v8H96Zm96 168H64V64h128Zm-80-104v64a8 8 0 0 1-16 0v-64a8 8 0 0 1 16 0Zm48 0v64a8 8 0 0 1-16 0v-64a8 8 0 0 1 16 0Z"/></svg>
                    </button>
                </div>

                <textarea id="permets" readonly rows="2" name="permets" class="hidden w-full rounded-lg border border-slate-200 bg-white px-4 py-2"></textarea>

                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700">Observations</span>
                    <textarea rows="3" name="observations" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2">{{ old('observations') }}</textarea>
                </label>
            </div>

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                <input type="submit" class="primary-action w-32 button" value="Ajouter">
            </div>
        </div>
    </div>
</form>

<script type="module">
        $("#hashost").click( function(){
            $("#visited").toggleClass('hidden');
        });
        $('#role').on('change', function() {
            if ($('#role option:selected').val() == "other") {
                $("#other").removeClass('hidden');
            }else{
                $("#other").addClass('hidden');
                $("#other_field").val(null);
            }
        }).trigger('change');
$(document).ready(function () {
        const elyctisConfig = {
            url: 'http://127.0.0.1:8765/read-card',
            token: 'change-this-local-token',
            intervalMs: 2500
        };
        let elyctisLastReadId = null;
        let elyctisReading = false;

        function setElyctisStatus(message, cssClass) {
            const status = $('#elyctis-status');
            status.removeClass('text-gray-500 text-green-600 text-red-600 text-orange-600');
            status.addClass(cssClass || 'text-gray-500');
            status.text(message || '');
        }

        function fillIfPresent(selector, value) {
            if (value !== undefined && value !== null && String(value).trim() !== '') {
                $(selector).val(value);
            }
        }

        function normalizeText(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        function updateSubjectVisibility(clearWhenHidden = true) {
            const categoryText = normalizeText($('#visit-category option:selected').text());
            const needsVisitSubject = categoryText.indexOf('avant') !== -1 || categoryText.indexOf('visite') !== -1;
            const needsInvitationSubject = categoryText.indexOf('invitation') !== -1;
            const shouldShow = needsVisitSubject || needsInvitationSubject;

            if (shouldShow) {
                $('#subject-wrapper').removeClass('hidden');
                $('#subject-field').prop('required', false);
                if (needsInvitationSubject) {
                    $('#subject-label').text("Objet de l'invitation et de qui");
                    $('#subject-field').attr('placeholder', "Ex: Reunion de coordination - invite par ...");
                } else {
                    $('#subject-label').text('Objet de visite');
                    $('#subject-field').attr('placeholder', 'Ex: Depot dossier, reunion, controle...');
                }
            } else {
                $('#subject-wrapper').addClass('hidden');
                $('#subject-field').prop('required', false);
                $('#subject-field').attr('placeholder', '');
                if (clearWhenHidden) {
                    $('#subject-field').val('');
                }
            }
        }

        $('#visit-category').on('change', function () {
            updateSubjectVisibility(true);
        });
        updateSubjectVisibility(false);
        $('form').on('submit', function () {
            updateSubjectVisibility(false);
        });

        function detectedIdTypeName(card) {
            const rawType = normalizeText(card.documentType || card.DocumentType);
            if (!rawType) {
                return '';
            }

            if (rawType === 'p' || rawType.indexOf('passport') !== -1 || rawType.indexOf('passeport') !== -1) {
                return 'passeport';
            }

            if (rawType === 'd' || rawType === 'dl' || rawType.indexOf('driving') !== -1 || rawType.indexOf('driver') !== -1 || rawType.indexOf('licence') !== -1 || rawType.indexOf('license') !== -1 || rawType.indexOf('permis') !== -1) {
                return 'permis de conduire';
            }

            if (rawType === 'i' || rawType === 'id' || rawType.indexOf('identity') !== -1 || rawType.indexOf('identite') !== -1 || rawType.indexOf('national') !== -1 || rawType.indexOf('carte') !== -1) {
                return "carte d'identite";
            }

            return '';
        }

        function applyDetectedIdType(card) {
            const expected = detectedIdTypeName(card);
            if (!expected) {
                return null;
            }

            const select = $('#elyctis-id-cat');
            let selected = null;
            select.find('option').each(function () {
                const optionText = normalizeText($(this).text());
                if (optionText === expected || optionText.indexOf(expected) !== -1 || expected.indexOf(optionText) !== -1) {
                    selected = $(this).val();
                    return false;
                }
            });

            if (selected !== null) {
                select.val(selected);
            }

            return selected;
        }

        function fillVisitorFromCard(card) {
            applyDetectedIdType(card);
            fillIfPresent('input[name="fname"]', card.lastName || card.LastName);
            fillIfPresent('input[name="lname"]', card.firstName || card.FirstName);
            fillIfPresent('input[name="cin"]', card.documentNumber || card.DocumentNumber);
            fillIfPresent('input[name="nin"]', card.nationalIdentificationNumber || card.NationalIdentificationNumber);
            const nationality = card.nationality || card.Nationality;
            const dateOfBirth = card.dateOfBirth || card.DateOfBirth;
            card.nationality = nationality;
            card.dateOfBirth = dateOfBirth;
            if (nationality || dateOfBirth) {
                const current = $('textarea[name="observations"]').val();
                const details = [
                    card.nationality ? 'Nationalite: ' + card.nationality : null,
                    card.dateOfBirth ? 'Date naissance: ' + card.dateOfBirth : null
                ].filter(Boolean).join(' | ');
                if (current.indexOf(details) === -1) {
                    $('textarea[name="observations"]').val((current ? current + "\n" : '') + details);
                }
            }
        }

        function normalizeElyctisResult(result) {
            return {
                success: result.success ?? result.Success,
                status: result.status ?? result.Status,
                errorCode: result.errorCode ?? result.ErrorCode,
                message: result.message ?? result.Message,
                readId: result.readId ?? result.ReadId,
                data: result.data ?? result.Data
            };
        }

        async function readElyctisCard() {
            if (elyctisReading) {
                return;
            }
            elyctisReading = true;
            $('#elyctis-read-btn').prop('disabled', true).addClass('opacity-60');
            setElyctisStatus('Lecture en cours...', 'text-gray-500');
            try {
                const response = await fetch(elyctisConfig.url, {
                    method: 'GET',
                    headers: {
                        'X-Elyctis-Token': elyctisConfig.token,
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                });
                const result = normalizeElyctisResult(await response.json());
                if (result.success && result.readId !== elyctisLastReadId) {
                    elyctisLastReadId = result.readId;
                    const card = result.data || {};
                    fillVisitorFromCard(card);
                    setElyctisStatus('Carte lue', 'text-green-600');
                } else if (!result.success && result.status !== 'no_card') {
                    const message = result.errorCode === 'ACCESS_CONTROL_REQUIRED'
                        ? 'Lecture impossible: MRZ scanner non lue'
                        : (result.message || 'Lecteur indisponible');
                    setElyctisStatus(message, 'text-orange-600');
                } else {
                    setElyctisStatus('Aucune carte detectee', 'text-orange-600');
                }
            } catch (error) {
                setElyctisStatus('Service Elyctis arrete', 'text-red-600');
            } finally {
                elyctisReading = false;
                $('#elyctis-read-btn').prop('disabled', false).removeClass('opacity-60');
            }
        }

        $('#elyctis-read-btn').on('click', readElyctisCard);

        $('#permetadd').on('change', function() {
            $('#permetadd').val() !="" ? $('#permets').removeClass('hidden'): $('#permets').addClass('hidden')
        });
        $('#addbtn').on('click', function() {
             const addPermet = document.getElementById('permetadd')
            const permets = document.getElementById('permets')
            if (addPermet.value != "" && !permets.value.match(addPermet.value) && !addPermet.value.match(",")) {
            permets.value == "" ? permets.value=addPermet.value : permets.value=permets.value+','+addPermet.value
            addPermet.value=""
            }
        });
        $('#clrbtn').on('click', function() {
            const permets = document.getElementById('permets')
            permets.value == "" ? "" : permets.value = ""
            $(permets).addClass('hidden')
        });
        });

        </script>

@endsection

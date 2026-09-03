@extends('Reception.layouts.master')

@section('body')
<form action="{{ route('p_add_visitors') }}" method="post" enctype="multipart/form-data" class="visitx-form">
    @csrf
    <input type="hidden" name="new_visitor" value="1">
    <input type="hidden" name="exists" value="">
    <input type="hidden" name="user" value="">

    <div class="w-full max-w-5xl py-2">
        <div class="form-card">
            <div class="visitx-form-hero">
                <div class="min-w-0">
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
            <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section id="new_user" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Visiteur</p>
                        <h3 class="mt-2 text-xl font-black text-slate-900">Identité</h3>
                    </div>

                    <div class="space-y-4">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">NIN <span class="text-red-600">*</span></span>
                            <input type="text" name="nin" required value="{{ old('nin') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="mb-1 block text-sm font-semibold text-slate-700">Nom <span class="text-red-600">*</span></span>
                                <input type="text" name="fname" required value="{{ old('fname') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-sm font-semibold text-slate-700">Prénom <span class="text-red-600">*</span></span>
                                <input type="text" name="lname" required value="{{ old('lname') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="mb-1 block text-sm font-semibold text-slate-700">Piece d'identite</span>
                                <select id="elyctis-id-cat" name="id_cat" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                                    @foreach ($id_types as $type)
                                        @php
                                            $typeName = strtolower(trim((string) $type->name));
                                        @endphp
                                        @if(in_array($typeName, ["carte d'identité", "carte d'identite", 'passeport', 'permis de conduire'], true))
                                            <option value="{{ $type->id }}" @selected(old('id_cat') == $type->id)>
                                                {{ $type->name === "Carte d'identité" ? 'Carte nationale' : $type->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-sm font-semibold text-slate-700">Numéro piece <span class="text-red-600">*</span></span>
                                <input type="text" name="cin" required value="{{ old('cin') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="mb-1 block text-sm font-semibold text-slate-700">Poste</span>
                                <select name="role" id="role" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old('role') == $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                    <option value="other" @selected(old('role') == 'other')>Autre</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-sm font-semibold text-slate-700">Heure d'entree</span>
                                <input type="datetime-local" name="date_entry" value="{{ old('date_entry', $cur_date) }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                            </label>
                        </div>

                        <label id="other" class="hidden block">
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Autre Poste</span>
                            <input id="other_field" type="text" name="other_value" value="{{ old('other_value') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-amber-400">
                        </label>
                    </div>
                </section>

                <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Badge</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900">Numéro badge</h3>
                        </div>
                        <button id="elyctis-photo-button" type="button" disabled class="group flex h-16 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100 text-[10px] font-semibold uppercase text-slate-400 shadow-sm transition hover:scale-[1.02] hover:border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 disabled:cursor-default disabled:hover:scale-100 sm:h-20 sm:w-16">
                            <span id="elyctis-photo-placeholder">Photo</span>
                            <img id="elyctis-photo" alt="Photo visiteur" class="hidden h-full w-full object-cover">
                        </button>
                    </div>

                    <div class="mt-5 space-y-4">
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Badge remis <span class="text-red-600">*</span></span>
                            <input type="text" name="badge_n" placeholder="Badge remis" required value="{{ old('badge_n') }}" class="w-full rounded-2xl border border-amber-300 bg-white px-5 py-4 text-2xl font-black tracking-wider text-slate-900 shadow-sm focus:border-amber-400">
                            @error('badge_n')
                                <span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Circuit de traitement</span>
                            <select id="workflow_type" name="workflow_type" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 shadow-sm focus:border-amber-400">
                                <option value="classic" @selected(old('workflow_type', 'classic') === 'classic')>Orientation classique</option>
                                <option value="bog" @selected(old('workflow_type') === 'bog')>BOG</option>
                            </select>
                            <p class="mt-2 text-xs text-slate-500">BOG reste géré par l'agent saisie badge pour l'entrée et la sortie.</p>
                        </label>

                       
                    </div>
                </aside>
            </div>

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                <input type="submit" class="primary-action w-32 button" value="Ajouter">
            </div>
        </div>
    </div>
</form>

<div id="elyctis-photo-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
    <div class="relative max-h-[92vh] w-full max-w-sm rounded-2xl bg-white p-3 shadow-2xl">
        <button id="elyctis-photo-close" type="button" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-xl font-bold text-slate-700 shadow hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400" aria-label="Fermer">×</button>
        <img id="elyctis-photo-large" alt="Photo visiteur agrandie" class="max-h-[84vh] w-full rounded-xl object-contain">
    </div>
</div>

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
        function syncWorkflowHost() {
            const isBog = $('#workflow_type').val() === 'bog';
            $('#hostname').val(isBog ? 'BOG' : '');
        }
        $('#workflow_type').on('change', syncWorkflowHost);
        syncWorkflowHost();
$(document).ready(function () {
        const elyctisConfig = {
            endpoints: ['http://127.0.0.1:8766', 'http://localhost:8766', 'http://127.0.0.1:8765', 'http://localhost:8765'],
            token: 'change-this-local-token',
            retryCount: 2,
            partialRetryCount: 1,
            retryDelayMs: 900,
            healthTimeoutMs: 5000,
            diagnosticsTimeoutMs: 5000,
            readTimeoutMs: 150000,
            installUrl: '/elyctis-client/elyctis-client.zip'
        };
        let elyctisLastReadId = null;
        let elyctisReading = false;
        let elyctisEndpoint = null;
        let elyctisPhotoSrc = '';

        function setElyctisStatus(message, cssClass) {
            const status = $('#elyctis-status');
            status.removeClass('text-gray-500 text-green-600 text-red-600 text-orange-600');
            status.addClass(cssClass || 'text-gray-500');
            status.text(message || '');
        }

        function setFieldValue(selector, value) {
            const nextValue = value === undefined || value === null ? '' : String(value).trim();
            $(selector).val(nextValue).trigger('input').trigger('change');
        }

        function pick(source, keys) {
            const lower = {};
            Object.keys(source || {}).forEach(function (key) {
                lower[String(key).toLowerCase()] = source[key];
            });
            for (const key of keys) {
                if (source && source[key] !== undefined && source[key] !== null && String(source[key]).trim() !== '') {
                    return source[key];
                }
                const folded = lower[String(key).toLowerCase()];
                if (folded !== undefined && folded !== null && String(folded).trim() !== '') {
                    return folded;
                }
            }
            return '';
        }

        function mrzCharValue(value) {
            const ch = String(value || '<').charAt(0);
            if (ch >= '0' && ch <= '9') return ch.charCodeAt(0) - 48;
            if (ch >= 'A' && ch <= 'Z') return ch.charCodeAt(0) - 55;
            return 0;
        }

        function computeMrzCheckDigit(value) {
            const weights = [7, 3, 1];
            return String(String(value || '').split('').reduce(function (sum, ch, index) {
                return sum + mrzCharValue(ch) * weights[index % 3];
            }, 0) % 10);
        }

        function cleanDocumentNumber(value) {
            return String(value || '').trim().toUpperCase().replace(/[<_\s]/g, '');
        }

        function documentNumberFromMrz(mrz) {
            const lines = String(mrz || '')
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n')
                .split('\n')
                .map(line => line.trim().toUpperCase().replace(/\s/g, '<'))
                .filter(Boolean);

            let field = '';
            let checkDigit = '';
            if (lines.length >= 3 && lines[0].length >= 15) {
                field = lines[0].substring(5, 14);
                checkDigit = lines[0].charAt(14);
            } else if (lines.length === 2 && lines[1].length >= 10) {
                field = lines[1].substring(0, 9);
                checkDigit = lines[1].charAt(9);
            }

            if (!field) return '';
            const chars = field.split('');
            const unknownIndexes = chars
                .map((ch, index) => /^[A-Z0-9<]$/.test(ch) ? -1 : index)
                .filter(index => index >= 0);

            if (/^\d$/.test(checkDigit) && unknownIndexes.length === 1) {
                const unknownIndex = unknownIndexes[0];
                for (const candidate of '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ<') {
                    chars[unknownIndex] = candidate;
                    if (computeMrzCheckDigit(chars.join('')) === checkDigit) {
                        return cleanDocumentNumber(chars.join(''));
                    }
                }
            }

            return cleanDocumentNumber(field);
        }

        function normalizeDocumentNumber(value, mrz) {
            const raw = String(value || '');
            const cleaned = cleanDocumentNumber(raw);
            const fromMrz = documentNumberFromMrz(mrz);
            if (fromMrz && (!cleaned || /[<_\s]/.test(raw) || fromMrz.length >= cleaned.length)) {
                return fromMrz;
            }
            return cleaned;
        }

        function normalizeNin(value) {
            const text = String(value || '').trim();
            const match = text.match(/\d{18}/);
            return match ? match[0] : text;
        }

        function photoSrcFromCard(card) {
            const photoBase64 = pick(card, ['photoBase64', 'PhotoBase64', 'photo', 'Photo']);
            if (!photoBase64) return '';
            if (String(photoBase64).indexOf('data:image/') === 0) {
                return String(photoBase64);
            }
            const mimeType = pick(card, ['photoMimeType', 'PhotoMimeType', 'mimeType', 'MimeType']) || 'image/jpeg';
            return 'data:' + mimeType + ';base64,' + photoBase64;
        }

        function updateVisitorPhoto(card) {
            const src = photoSrcFromCard(card || {});
            const photo = $('#elyctis-photo');
            const placeholder = $('#elyctis-photo-placeholder');
            const button = $('#elyctis-photo-button');
            elyctisPhotoSrc = src;
            if (src) {
                photo.attr('src', src);
                photo.removeClass('hidden');
                placeholder.addClass('hidden');
                button.prop('disabled', false).attr('title', 'Afficher la photo');
            } else {
                photo.removeAttr('src');
                photo.addClass('hidden');
                placeholder.removeClass('hidden');
                button.prop('disabled', true).removeAttr('title');
            }
        }

        function openVisitorPhoto() {
            if (!elyctisPhotoSrc) return;
            $('#elyctis-photo-large').attr('src', elyctisPhotoSrc);
            $('#elyctis-photo-modal').removeClass('hidden').addClass('flex');
        }

        function closeVisitorPhoto() {
            $('#elyctis-photo-modal').addClass('hidden').removeClass('flex');
            $('#elyctis-photo-large').removeAttr('src');
        }

        function appendObservation(text) {
            if (!text) return;
            const field = $('[name="observations"]').first();
            if (!field.length) return;
            const current = String(field.val() || '');
            if (current.indexOf(text) === -1) {
                field.val((current ? current + "\n" : '') + text).trigger('input').trigger('change');
            }
        }

        function normalizeCard(card) {
            card = card || {};
            const mrz = pick(card, ['mrz', 'Mrz', 'MRZ']);
            return {
                firstName: pick(card, ['firstName', 'FirstName', 'givenNames', 'GivenNames', 'givenName', 'name']),
                lastName: pick(card, ['lastName', 'LastName', 'familyName', 'FamilyName', 'surname', 'Surname']),
                documentNumber: normalizeDocumentNumber(pick(card, ['documentNumber', 'DocumentNumber', 'documentNo', 'docNum', 'cardNumber', 'licenceNumber', 'LicenceNumber']), mrz),
                nin: normalizeNin(pick(card, ['nationalIdentificationNumber', 'NationalIdentificationNumber', 'personalNumber', 'PersonalNumber', 'personalNumberDg11', 'PersonalNumberDg11', 'nin', 'NIN'])),
                nationality: pick(card, ['nationality', 'Nationality', 'nationalityIso', 'NationalityIso']),
                dateOfBirth: pick(card, ['dateOfBirth', 'DateOfBirth', 'birthDate', 'BirthDate']),
                documentType: pick(card, ['documentType', 'DocumentType']),
                raw: card
            };
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
            const normalized = normalizeCard(card);
            const rawType = normalizeText(normalized.documentType);
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
            const normalized = normalizeCard(card);
            updateVisitorPhoto(card);
            applyDetectedIdType(card);
            setFieldValue('input[name="fname"]', normalized.lastName);
            setFieldValue('input[name="lname"]', normalized.firstName);
            setFieldValue('input[name="cin"]', normalized.documentNumber);
            setFieldValue('input[name="nin"]', normalized.nin);
            const nationality = normalized.nationality;
            const dateOfBirth = normalized.dateOfBirth;
            if (nationality || dateOfBirth) {
                const details = [
                    nationality ? 'Nationalite: ' + nationality : null,
                    dateOfBirth ? 'Date naissance: ' + dateOfBirth : null
                ].filter(Boolean).join(' | ');
                appendObservation(details);
            }
            return normalized;
        }

        function markNewVisitorMode() {
            $('input[name="new_visitor"]').val('1');
            $('input[name="exists"]').val('');
            $('input[name="user"]').val('');
        }

        function markExistingVisitorMode(visitor) {
            $('input[name="new_visitor"]').val('');
            $('input[name="exists"]').val('1');
            $('input[name="user"]').val(visitor.id);
        }

        async function detectExistingVisitor(card) {
            const normalized = normalizeCard(card);
            const params = new URLSearchParams();
            if (normalized.nin) params.set('nin', normalized.nin);
            if (normalized.documentNumber) params.set('cin', normalized.documentNumber);
            const idType = $('#elyctis-id-cat').val();
            if (idType) params.set('id_cat', idType);
            if (!params.has('nin') && !params.has('cin')) {
                markNewVisitorMode();
                return null;
            }
            try {
                const response = await fetch('/guests/find-card-visitor?' + params.toString(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!response.ok) {
                    markNewVisitorMode();
                    return null;
                }
                const body = await response.json();
                if (body && body.found && body.visitor) {
                    markExistingVisitorMode(body.visitor);
                    return body.visitor;
                }
            } catch (error) {
                // Existing visitor lookup is a convenience; do not block card filling.
            }
            markNewVisitorMode();
            return null;
        }

        function normalizeElyctisResult(result) {
            return {
                success: result.success ?? result.Success ?? result.ok ?? result.Ok,
                status: result.status ?? result.Status,
                errorCode: result.errorCode ?? result.ErrorCode,
                message: result.message ?? result.Message,
                partial: result.partial ?? result.Partial,
                warning: result.warning ?? result.Warning,
                readId: result.readId ?? result.ReadId,
                data: result.data ?? result.Data
            };
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        function endpointUrl(baseUrl, path) {
            const separator = path.indexOf('?') === -1 ? '?' : '&';
            return baseUrl + path + separator + 'token=' + encodeURIComponent(elyctisConfig.token);
        }

        async function fetchWithTimeout(url, options, timeoutMs) {
            const controller = new AbortController();
            const timer = setTimeout(function () {
                controller.abort();
            }, timeoutMs);
            try {
                return await fetch(url, Object.assign({}, options, { signal: controller.signal }));
            } finally {
                clearTimeout(timer);
            }
        }

        async function resolveElyctisEndpoint() {
            if (elyctisEndpoint) {
                return elyctisEndpoint;
            }
            let lastError = null;
            for (const endpoint of elyctisConfig.endpoints) {
                try {
                    const response = await fetchWithTimeout(endpointUrl(endpoint, '/health'), {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }, elyctisConfig.healthTimeoutMs);
                    const body = await response.json();
                    if (response.ok && (body.success === true || body.ok === true)) {
                        const diagnosticsResponse = await fetchWithTimeout(endpointUrl(endpoint, '/diagnostics'), {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' },
                            cache: 'no-store'
                        }, elyctisConfig.diagnosticsTimeoutMs);
                        const diagnosticsBody = await diagnosticsResponse.json();
                        if (!diagnosticsResponse.ok || !(diagnosticsBody.success === true || diagnosticsBody.ok === true)) {
                            throw new Error('Ancien client Elyctis installe');
                        }
                        elyctisEndpoint = endpoint;
                        return endpoint;
                    }
                } catch (error) {
                    lastError = error;
                }
            }
            const error = lastError || new Error('Service Elyctis indisponible');
            if (error && /not_found|404|Ancien client Elyctis/i.test(String(error.message || error))) {
                throw new Error('Ancien client Elyctis installe. Reinstallez: ' + elyctisConfig.installUrl);
            }
            throw error;
        }

        async function fetchElyctisJsonWithRetry(path, options) {
            let lastError = null;
            for (let attempt = 0; attempt <= elyctisConfig.retryCount; attempt++) {
                try {
                    const endpoint = await resolveElyctisEndpoint();
                    const response = await fetchWithTimeout(endpointUrl(endpoint, path), options, elyctisConfig.readTimeoutMs);
                    const text = await response.text();
                    if (!text) {
                        throw new Error('Empty Elyctis response');
                    }
                    const result = normalizeElyctisResult(JSON.parse(text));
                    if (!response.ok) {
                        throw new Error(result.message || 'HTTP ' + response.status);
                    }
                    return result;
                } catch (error) {
                    lastError = error;
                    elyctisEndpoint = null;
                    if (attempt < elyctisConfig.retryCount) {
                        setElyctisStatus('Service Elyctis en redemarrage...', 'text-orange-600');
                        await sleep(elyctisConfig.retryDelayMs * (attempt + 1));
                    }
                }
            }
            throw lastError || new Error('Elyctis unavailable');
        }

        async function readElyctisCard() {
            if (elyctisReading) {
                return;
            }
            elyctisReading = true;
            $('#elyctis-read-btn').prop('disabled', true).addClass('opacity-60');
            setElyctisStatus('Lecture en cours...', 'text-gray-500');
            try {
                let result = await fetchElyctisJsonWithRetry('/read-card', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                });
                for (let partialAttempt = 0; partialAttempt < elyctisConfig.partialRetryCount; partialAttempt++) {
                    const normalizedPartial = normalizeCard(result.data || {});
                    if (!result.success || !result.partial || normalizedPartial.nin) {
                        break;
                    }
                    setElyctisStatus('Lecture partielle, nouvelle tentative...', 'text-orange-600');
                    await sleep(elyctisConfig.retryDelayMs);
                    const retryResult = await fetchElyctisJsonWithRetry('/read-card', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        cache: 'no-store'
                    });
                    const normalizedRetry = normalizeCard(retryResult.data || {});
                    if (retryResult.success && (!retryResult.partial || normalizedRetry.nin)) {
                        result = retryResult;
                    }
                }
                if (result.success && result.readId !== elyctisLastReadId) {
                    elyctisLastReadId = result.readId;
                    const card = result.data || {};
                    const normalized = fillVisitorFromCard(card);
                    if (result.warning) {
                        appendObservation(result.warning);
                    }
                    const existingVisitor = await detectExistingVisitor(card);
                    const prefix = result.partial ? 'Lecture partielle' : 'Carte lue';
                    const suffix = existingVisitor ? ' - visiteur existant' : '';
                    setElyctisStatus(prefix + suffix, result.partial && !existingVisitor ? 'text-orange-600' : 'text-green-600');
                } else if (!result.success && result.status !== 'no_card') {
                    const message = result.errorCode === 'ACCESS_CONTROL_REQUIRED'
                        ? 'Lecture impossible: MRZ non lue. Verifiez position/port COM.'
                        : (result.message || 'Lecteur indisponible');
                    setElyctisStatus(message, 'text-orange-600');
                } else {
                    setElyctisStatus('Aucune carte detectee', 'text-orange-600');
                }
            } catch (error) {
                setElyctisStatus(error.message || ('Service Elyctis indisponible sur ce PC. Installer: ' + elyctisConfig.installUrl), 'text-red-600');
            } finally {
                elyctisReading = false;
                $('#elyctis-read-btn').prop('disabled', false).removeClass('opacity-60');
            }
        }

        $('#elyctis-read-btn').on('click', readElyctisCard);
        $('#elyctis-photo-button').on('click', openVisitorPhoto);
        $('#elyctis-photo-close').on('click', closeVisitorPhoto);
        $('#elyctis-photo-modal').on('click', function (event) {
            if (event.target === this) {
                closeVisitorPhoto();
            }
        });
        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeVisitorPhoto();
            }
        });

        });

        </script>

@endsection

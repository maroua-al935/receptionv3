@php
    $visit = $data[0] ?? null;

    $text = function ($value, $fallback = 'Non renseigne') {
        $value = is_string($value) ? trim($value) : $value;
        return ($value !== null && $value !== '') ? $value : $fallback;
    };

    $formatDate = function ($value, $fallback = 'Non renseigne') {
        if (!$value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $formatValue = function ($value) use ($text) {
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $text($value, 'Vide');
    };

    $profile = (int) optional(Auth::guard('web')->user())->profile;
    $isPresident = in_array($profile, [1, 2, 3], true);
    $canEdit = ($isPresident || in_array($profile, [4, 5, 6, 7, 8, 9], true)) && $visit && isset($visit->visit_id);
    $canDelete = $isPresident && $visit && isset($visit->visit_id);
    $backUrl = $backUrl ?? route('home');
    if (in_array($profile, [4, 8, 9], true)) {
        $backUrl = route('home');
    }
    $fullName = $visit
        ? trim(collect([$visit->firstname ?? null, $visit->lastname ?? null])->filter()->implode(' '))
        : '';
    $fullName = $fullName !== '' ? $fullName : ($title ?? 'Visiteur');

    $employeeName = $visit ? trim(collect([
        $visit->user_firstname ?? null,
        $visit->user_lastname ?? null,
    ])->filter()->implode(' ')) : '';
    $employeeName = $employeeName !== '' ? $employeeName : ($visit->usrname ?? null);

    $workflowType = strtolower(trim((string) ($visit->workflow_type ?? 'classic')));
    $serviceLabel = $workflowType === 'bog'
        ? 'BOG'
        : ($visit->service ?? $visit->service_full_dn ?? $visit->service_dn ?? null);
    $positionLabel = $visit->position ?? $visit->position_name ?? $visit->role_name ?? null;
    $wilayaValue = $visit->wilaya ?? $visit->wilaya_name ?? null;
    $wilayaList = is_string($wilayaValue) ? json_decode($wilayaValue, true) : $wilayaValue;
    $wilayaNames = [
        '01' => 'Adrar', '02' => 'Chlef', '03' => 'Laghouat', '04' => 'Oum El Bouaghi', '05' => 'Batna', '06' => 'Béjaïa', '07' => 'Biskra', '08' => 'Béchar', '09' => 'Blida', '10' => 'Bouira', '11' => 'Tamanrasset', '12' => 'Tébessa', '13' => 'Tlemcen', '14' => 'Tiaret', '15' => 'Tizi Ouzou', '16' => 'Alger', '17' => 'Djelfa', '18' => 'Jijel', '19' => 'Sétif', '20' => 'Saïda', '21' => 'Skikda', '22' => 'Sidi Bel Abbès', '23' => 'Annaba', '24' => 'Guelma', '25' => 'Constantine', '26' => 'Médéa', '27' => 'Mostaganem', '28' => "M'Sila", '29' => 'Mascara', '30' => 'Ouargla', '31' => 'Oran', '32' => 'El Bayadh', '33' => 'Illizi', '34' => 'Bordj Bou Arreridj', '35' => 'Boumerdès', '36' => 'El Tarf', '37' => 'Tindouf', '38' => 'Tissemsilt', '39' => 'El Oued', '40' => 'Khenchela', '41' => 'Souk Ahras', '42' => 'Tipaza', '43' => 'Mila', '44' => 'Aïn Defla', '45' => 'Naâma', '46' => 'Aïn Témouchent', '47' => 'Ghardaïa', '48' => 'Relizane', '49' => "El M'Ghair", '50' => 'El Meniaa', '51' => 'Ouled Djellal', '52' => 'Bordj Badji Mokhtar', '53' => 'Béni Abbès', '54' => 'Timimoun', '55' => 'Touggourt', '56' => 'Djanet', '57' => 'In Salah', '58' => 'In Guezzam', '59' => 'Aflou', '60' => 'Barika', '61' => 'El Kantara', '62' => 'Bir El Ater', '63' => 'El Aricha', '64' => 'Ksar Chellala', '65' => 'Aïn Oussara', '66' => 'Messaad', '67' => 'Ksar El Boukhari', '68' => 'Bou Saâda', '69' => 'El Abiodh Sidi Cheikh',
    ];
    $wilayaLabel = is_array($wilayaList)
        ? implode(', ', array_filter(array_map(fn ($value) => $wilayaNames[(string) $value] ?? $value, $wilayaList)))
        : ($wilayaNames[(string) $wilayaValue] ?? $wilayaValue);

    $validatorName = $visit ? trim(collect([
        $visit->validation_by_firstname ?? null,
        $visit->validation_by_lastname ?? null,
    ])->filter()->implode(' ')) : '';
    $validatorName = $validatorName !== '' ? $validatorName : ($visit->validation_by_name ?? null);

    $statusCode = is_numeric($visit->status ?? null) ? (int) $visit->status : -1;
    $statusMap = [
        0 => ['label' => 'En attente', 'class' => 'visit2026-status-waiting'],
        1 => ['label' => 'En cours', 'class' => 'visit2026-status-active'],
        2 => ['label' => 'Terminee', 'class' => 'visit2026-status-done'],
        3 => ['label' => 'Renvoyee', 'class' => 'visit2026-status-returned'],
    ];
    $status = $statusMap[$statusCode] ?? ['label' => 'Statut inconnu', 'class' => 'visit2026-status-muted'];

    $documentPath = $visit ? trim(str_replace('\\', '/', (string) ($visit->filepath ?? ''))) : '';
    if ($documentPath !== '') {
        $documentPath = preg_replace('/^public\//', 'storage/', $documentPath);
    }
    $documentUrl = $documentPath !== '' ? asset($documentPath) : null;

    $identityRows = [
        ['label' => 'Nom complet', 'value' => $fullName],
        ['label' => 'Type de piece', 'value' => $visit->id_type ?? null],
        ['label' => 'Numero piece', 'value' => trim(($visit->cin ?? '') . (($visit->nin ?? '') !== '' ? ' | NIN: ' . $visit->nin : ''))],
        ['label' => 'Wilaya', 'value' => $wilayaLabel],
        ['label' => 'Poste', 'value' => $positionLabel],
        ['label' => 'Societe', 'value' => $visit->organisation ?? null],
    ];

    $destinationRows = [
        ['label' => 'Objet', 'value' => $visit->subject ?? null],
        ['label' => 'Categorie', 'value' => $visit->category ?? null],
        ['label' => 'Service visite', 'value' => $serviceLabel],
        ['label' => 'Service DN', 'value' => $visit->service_dn ?? null],
        ['label' => 'Service superieur', 'value' => $visit->service_superior ?? null],
        ['label' => 'Personne visitee', 'value' => $employeeName],
        ['label' => 'Email personne visitee', 'value' => $visit->user_email ?? null],
        ['label' => 'Telephone personne visitee', 'value' => $visit->user_phone ?? null],
        ['label' => 'A un hote', 'value' => isset($visit->hashost) ? ((int) $visit->hashost === 1 ? 'Oui' : 'Non') : null],
        ['label' => 'Badge', 'value' => $visit->badge_n ?? null],
    ];

    $timelineRows = [
        ['label' => 'Entree', 'value' => $visit->entry_date ?? null, 'meta' => 'Arrivee du visiteur'],
        ['label' => 'Acceptation', 'value' => $visit->accept_time ?? null, 'meta' => 'Prise en charge'],
        ['label' => 'Validation', 'value' => $visit->validation_time ?? null, 'meta' => $validatorName ? 'Par ' . $validatorName : 'Validation'],
        ['label' => 'Renvoi', 'value' => $visit->sendup_time ?? null, 'meta' => 'Retour / transfert'],
        ['label' => 'Sortie', 'value' => $visit->exit_date ?? null, 'meta' => 'Fin de visite'],
    ];

    $systemRows = [
        ['label' => 'ID visite', 'value' => $visit->visit_id ?? null],
        ['label' => 'ID visiteur', 'value' => $visit->visitor_id ?? ($visit->visitor_id_ref ?? null)],
        ['label' => 'ID organisation', 'value' => $visit->organisation_id ?? null],
        ['label' => 'ID categorie', 'value' => $visit->category_id ?? null],
        ['label' => 'ID service', 'value' => $visit->service_id ?? null],
        ['label' => 'ID personne visitee', 'value' => $visit->emp_visited_id ?? null],
        ['label' => 'ID validateur', 'value' => $visit->validation_by ?? null],
        ['label' => 'ID document', 'value' => $visit->attachment_id ?? null],
        ['label' => 'Supprime', 'value' => isset($visit->visit_is_deleted) ? ((int) $visit->visit_is_deleted === 1 ? 'Oui' : 'Non') : null],
    ];

    $auditsList = isset($audits) ? collect($audits) : collect();
    $embedded = (bool) ($embedded ?? false);
@endphp

@if(!$visit)
    <div class="visit2026-empty">Aucune donnee disponible pour cette visite.</div>
@else
    <style>
        .visit2026-page {
            --visit-ink: #0f172a;
            --visit-muted: #64748b;
            --visit-soft: #f8fafc;
            --visit-line: #e2e8f0;
            --visit-blue: #2563eb;
            --visit-green: #059669;
            --visit-amber: #d97706;
            --visit-red: #dc2626;
            color: var(--visit-ink);
            letter-spacing: 0;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 26%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent 22%),
                linear-gradient(180deg, #f8fbff 0%, #ffffff 42%, #f8fafc 100%);
        }

        .visit2026-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.10) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.10) 1px, transparent 1px);
            background-size: 28px 28px;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.18), transparent 75%);
            pointer-events: none;
        }

        .visit2026-page * {
            letter-spacing: 0;
        }

        .visit2026-shell {
            display: grid;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .visit2026-topbar,
        .visit2026-hero,
        .visit2026-section,
        .visit2026-document {
            border: 1px solid var(--visit-line);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
        }

        .visit2026-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.82);
        }

        .visit2026-back,
        .visit2026-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            border-radius: 12px;
            border: 1px solid var(--visit-line);
            background: #fff;
            color: var(--visit-ink);
            padding: 0 14px;
            font-weight: 700;
            font-size: 14px;
            transition: border-color 160ms ease, background 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .visit2026-back:hover,
        .visit2026-action:hover {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            transform: translateY(-1px);
        }

        .visit2026-action-primary {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .visit2026-eyebrow {
            margin: 0;
            color: var(--visit-muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .visit2026-title {
            margin: 4px 0 0;
            color: var(--visit-ink);
            font-size: 28px;
            line-height: 1.15;
            font-weight: 900;
        }

        .visit2026-subtitle {
            margin: 8px 0 0;
            color: var(--visit-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .visit2026-hero {
            padding: 22px;
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(255, 255, 255, 0.96) 46%, rgba(16, 185, 129, 0.08)),
                #fff;
        }

        .visit2026-hero::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15), rgba(37, 99, 235, 0));
            pointer-events: none;
        }

        .visit2026-hero-main {
            display: grid;
            gap: 16px;
            align-items: start;
        }

        .visit2026-avatar {
            display: flex;
            width: 72px;
            height: 72px;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(135deg, #e0e7ff, #dbeafe);
            color: #1d4ed8;
            font-size: 26px;
            font-weight: 900;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.14);
        }

        .visit2026-person {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .visit2026-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .visit2026-status::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
        }

        .visit2026-status-waiting { background: #fffbeb; color: var(--visit-amber); }
        .visit2026-status-active { background: #ecfdf5; color: var(--visit-green); }
        .visit2026-status-done { background: #f1f5f9; color: #475569; }
        .visit2026-status-returned { background: #fef2f2; color: var(--visit-red); }
        .visit2026-status-muted { background: #f8fafc; color: #64748b; }

        .visit2026-metrics {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .visit2026-metric {
            min-width: 0;
            border: 1px solid var(--visit-line);
            border-radius: 14px;
            background: var(--visit-soft);
            padding: 14px 15px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .visit2026-metric-button {
            width: 100%;
            border: 0;
            color: inherit;
            text-align: left;
            cursor: pointer;
            transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
        }

        .visit2026-metric-button:hover,
        .visit2026-metric-button:focus-visible {
            border-color: #93c5fd;
            background: #eff6ff;
            outline: none;
            transform: translateY(-1px);
        }

        .visit2026-metric span,
        .visit2026-label {
            display: block;
            color: var(--visit-muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .visit2026-metric strong,
        .visit2026-value {
            display: block;
            margin-top: 4px;
            color: var(--visit-ink);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .visit2026-grid {
            display: grid;
            gap: 16px;
        }

        .visit2026-section,
        .visit2026-document {
            padding: 16px;
        }

        .visit2026-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--visit-line);
        }

        .visit2026-section-title {
            margin: 0;
            color: var(--visit-ink);
            font-size: 17px;
            font-weight: 900;
        }

        .visit2026-section-note {
            margin: 4px 0 0;
            color: var(--visit-muted);
            font-size: 13px;
        }

        .visit2026-detail-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr;
        }

        .visit2026-field {
            min-width: 0;
            padding: 12px 14px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 1));
        }

        .visit2026-timeline {
            display: grid;
            gap: 0;
        }

        .visit2026-step {
            display: grid;
            grid-template-columns: 28px 1fr;
            gap: 10px;
            padding: 13px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .visit2026-step:last-child {
            border-bottom: 0;
        }

        .visit2026-dot {
            width: 14px;
            height: 14px;
            margin-top: 3px;
            border-radius: 999px;
            border: 3px solid #dbeafe;
            background: #2563eb;
            box-shadow: 0 0 0 4px #eff6ff;
        }

        .visit2026-dot-empty {
            border-color: #e2e8f0;
            background: #cbd5e1;
            box-shadow: 0 0 0 4px #f8fafc;
        }

        .visit2026-document-frame {
            overflow: hidden;
            border-radius: 14px;
            border: 1px solid var(--visit-line);
            background: var(--visit-soft);
        }

        .visit2026-document-frame img {
            display: block;
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            background: #fff;
        }

        .visit2026-empty-doc,
        .visit2026-empty {
            display: flex;
            min-height: 180px;
            align-items: center;
            justify-content: center;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            color: var(--visit-muted);
            font-weight: 700;
            text-align: center;
            padding: 18px;
        }

        .visit2026-observation {
            margin: 14px 0 0;
            border-left: 4px solid #bfdbfe;
            background: #f8fafc;
            padding: 14px;
            color: #334155;
            line-height: 1.65;
            border-radius: 0 14px 14px 0;
            white-space: pre-wrap;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .visit2026-audit {
            border-bottom: 1px solid var(--visit-line);
            padding: 14px 0;
        }

        .visit2026-audit:last-child {
            border-bottom: 0;
        }

        .visit2026-audit-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .visit2026-audit-title {
            margin: 0;
            font-size: 14px;
            font-weight: 900;
            color: var(--visit-ink);
        }

        .visit2026-audit-meta {
            color: var(--visit-muted);
            font-size: 13px;
        }

        .visit2026-change-table {
            margin-top: 10px;
            overflow: auto;
            border: 1px solid var(--visit-line);
            border-radius: 8px;
        }

        .visit2026-change-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 520px;
            font-size: 13px;
        }

        .visit2026-change-table th,
        .visit2026-change-table td {
            border-bottom: 1px solid #edf2f7;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        .visit2026-change-table th {
            background: #f8fafc;
            color: var(--visit-muted);
            font-weight: 900;
            text-transform: uppercase;
        }

        .visit2026-change-table tr:last-child td {
            border-bottom: 0;
        }

        .visit2026-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(15, 23, 42, 0.58);
        }

        .visit2026-modal.is-open {
            display: flex;
        }

        .visit2026-modal-dialog {
            width: min(980px, 100%);
            max-height: min(860px, 92vh);
            overflow: auto;
            border: 1px solid var(--visit-line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
        }

        .visit2026-modal-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 20px;
            border-bottom: 1px solid var(--visit-line);
        }

        .visit2026-modal-close {
            display: inline-flex;
            width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--visit-line);
            border-radius: 10px;
            background: #fff;
            color: var(--visit-muted);
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
        }

        .visit2026-modal-close:hover,
        .visit2026-modal-close:focus-visible {
            border-color: #93c5fd;
            color: #1d4ed8;
            outline: none;
        }

        .visit2026-modal-body {
            display: grid;
            gap: 18px;
            padding: 20px;
        }

        .visit2026-modal-section-title {
            margin: 0 0 10px;
            color: var(--visit-ink);
            font-size: 15px;
            font-weight: 900;
        }

        .visit2026-legacy-details {
            display: none;
        }

        .visit2026-passage {
            padding: 22px;
        }

        .visit2026-passage-title {
            margin: 0;
            color: var(--visit-ink);
            font-size: 24px;
            font-weight: 900;
        }

        .visit2026-passage-subtitle {
            margin: 6px 0 18px;
            color: var(--visit-muted);
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .visit2026-hero-main {
                grid-template-columns: 1fr 320px;
            }

            .visit2026-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .visit2026-grid {
                grid-template-columns: minmax(0, 1.4fr) minmax(340px, 0.8fr);
                align-items: start;
            }
        }
    </style>

    <div class="visit2026-page visit2026-shell">
            @if(!$embedded)
            <div class="visit2026-topbar">
            <a href="{{ $backUrl }}" class="visit2026-back">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m7.825 13l5.6 5.6L12 20l-8-8l8-8l1.425 1.4l-5.6 5.6H20v2z"/></svg>
                Retour
            </a>
                <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                @if($documentUrl)
                    <a href="{{ $documentUrl }}" target="_blank" class="visit2026-action">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm-1 1.5L18.5 9H13zM8 13h8v2H8zm0 4h8v2H8z"/></svg>
                        Document
                    </a>
                @endif
                @if($canEdit)
                    <a href="{{ route('i_edit_visitors', $visit->visit_id) }}" class="visit2026-action visit2026-action-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 19h1.4l8.625-8.625l-1.4-1.4L5 17.6zm-2 2v-4.25L15.025 4.725q.3-.3.675-.45t.775-.15t.763.15t.662.45l1.375 1.4q.275.3.425.663t.15.737q0 .4-.137.775t-.438.675L7.25 21z"/></svg>
                        Modifier
                    </a>
                @endif
                @if($canDelete)
                    <form action="{{ route('p_delete_visitors', $visit->visit_id) }}" method="POST" onsubmit="return confirm('Supprimer cette visite ?');">
                        @csrf
                        <button type="submit" class="visit2026-action" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;">
                            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 3.75h6A1.75 1.75 0 0 1 16.75 5.5V6H20v1.5h-1.06l-.82 10.2A2.25 2.25 0 0 1 15.88 20H8.12a2.25 2.25 0 0 1-2.24-2.3L5.06 7.5H4V6h3.25v-.5A1.75 1.75 0 0 1 9 3.75m1.5 3h3v-.5a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25zm-2.3 2.25.55 8.75h.75l-.5-8.75zm4.55 0-.5 8.75h.75l.55-8.75z"/></svg>
                            Supprimer
                        </button>
                    </form>
                @endif
            </div>
            </div>
            @endif

        <section class="visit2026-section visit2026-passage">
            <h1 class="visit2026-passage-title">Passage</h1>
            <p class="visit2026-passage-subtitle">Details du passage visiteur</p>
            <div class="visit2026-detail-grid">
                
                <div class="visit2026-field"><span class="visit2026-label">Service</span><strong class="visit2026-value">{{ $text($serviceLabel) }}</strong></div>
                <div class="visit2026-field"><span class="visit2026-label">Badge</span><strong class="visit2026-value">{{ $text($visit->badge_n ?? null) }}</strong></div>
                @foreach($identityRows as $row)
                    <div class="visit2026-field">
                        <span class="visit2026-label">{{ $row['label'] }}</span>
                        <strong class="visit2026-value">{{ $text($row['value'] ?? null) }}</strong>
                    </div>
                @endforeach
                @foreach($timelineRows as $row)
                    <div class="visit2026-field">
                        <span class="visit2026-label">{{ $row['label'] }}</span>
                        <strong class="visit2026-value">{{ $formatDate($row['value'] ?? null) }}</strong>
                    </div>
                @endforeach 
            </div>
        </section>

        <div class="visit2026-legacy-details">
        <section class="visit2026-hero">
            <div class="visit2026-hero-main">
                <div class="visit2026-person">
                    <div class="visit2026-avatar">{{ strtoupper(substr($fullName, 0, 1)) }}</div>
                    <div>
                        <p class="visit2026-eyebrow">{{ $eyebrow ?? 'Fiche visite 2026' }}</p>
                        <h1 class="visit2026-title">{{ $fullName }}</h1>
                        <p class="visit2026-subtitle">{{ $subtitle ?? 'Details complets du visiteur, du passage et du suivi.' }}</p>
                        <div style="margin-top:12px;">
                            <span class="visit2026-status {{ $status['class'] }}">{{ $status['label'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="visit2026-metrics">
                    <button type="button" class="visit2026-metric visit2026-metric-button" data-visit-details-open>
                        <span>Entree</span>
                        <strong>{{ $formatDate($visit->entry_date ?? null) }}</strong>
                    </button>
                    <button type="button" class="visit2026-metric visit2026-metric-button" data-visit-details-open>
                        <span>Sortie</span>
                        <strong>{{ $formatDate($visit->exit_date ?? null) }}</strong>
                    </button>
                    <button type="button" class="visit2026-metric visit2026-metric-button" data-visit-details-open>
                        <span>Service</span>
                        <strong>{{ $text($serviceLabel) }}</strong>
                    </button>
                    <button type="button" class="visit2026-metric visit2026-metric-button" data-visit-details-open>
                        <span>Badge</span>
                        <strong>{{ $text($visit->badge_n ?? null) }}</strong>
                    </button>
                    <button type="button" class="visit2026-metric visit2026-metric-button" data-visit-details-open>
                        <span>NIN</span>
                        <strong>{{ $text($visit->nin ?? null) }}</strong>
                    </button>
                </div>
            </div>
        </section>

        <div class="visit2026-grid">
            <div class="visit2026-shell">
                <section class="visit2026-section">
                    <div class="visit2026-section-head">
                        <div>
                            <h2 class="visit2026-section-title">Identite visiteur</h2>
                            <p class="visit2026-section-note">Informations personnelles, piece et societe.</p>
                        </div>
                    </div>
                    <div class="visit2026-detail-grid">
                        @foreach($identityRows as $row)
                            <div class="visit2026-field">
                                <span class="visit2026-label">{{ $row['label'] }}</span>
                                <strong class="visit2026-value">{{ $text($row['value'] ?? null) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>
@if(in_array($profile, [1, 2]))
                <section class="visit2026-section">
                    <div class="visit2026-section-head">
                        <div>
                            <h2 class="visit2026-section-title">Visite et destination</h2>
                            <p class="visit2026-section-note">Objet, hote, service cible et informations de traitement.</p>
                        </div>
                    </div>
                    <div class="visit2026-detail-grid">
                        @foreach($destinationRows as $row)
                            <div class="visit2026-field">
                                <span class="visit2026-label">{{ $row['label'] }}</span>
                                <strong class="visit2026-value">{{ $text($row['value'] ?? null) }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <div class="visit2026-observation">
                        <span class="visit2026-label">Observations</span>
                        <div style="margin-top:6px;">{{ $text($visit->observations ?? null, 'Aucune observation.') }}</div>
                    </div>
                </section>

                <section class="visit2026-section">
                    <div class="visit2026-section-head">
                        <div>
                            <h2 class="visit2026-section-title">Historique de traitement</h2>
                            <p class="visit2026-section-note">Dates clefs triees selon le parcours de la visite.</p>
                        </div>
                    </div>
                    <div class="visit2026-timeline">
                        @foreach($timelineRows as $row)
                            @php $hasDate = !empty($row['value']); @endphp
                            <div class="visit2026-step">
                                <div class="{{ $hasDate ? 'visit2026-dot' : 'visit2026-dot visit2026-dot-empty' }}"></div>
                                <div>
                                    <span class="visit2026-label">{{ $row['label'] }}</span>
                                    <strong class="visit2026-value">{{ $formatDate($row['value'] ?? null) }}</strong>
                                    <div class="visit2026-section-note">{{ $row['meta'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="visit2026-section">
                    <div class="visit2026-section-head">
                        <div>
                            <h2 class="visit2026-section-title">References systeme</h2>
                            <p class="visit2026-section-note">Identifiants techniques utiles au suivi et au support.</p>
                        </div>
                    </div>
                    <div class="visit2026-detail-grid">
                        @foreach($systemRows as $row)
                            <div class="visit2026-field">
                                <span class="visit2026-label">{{ $row['label'] }}</span>
                                <strong class="visit2026-value">{{ $text($row['value'] ?? null) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>


                <section class="visit2026-section">
                    <div class="visit2026-section-head">
                        <div>
                            <h2 class="visit2026-section-title">Journal des changements</h2>
                            <p class="visit2026-section-note">Actions recentes et valeurs modifiees.</p>
                        </div>
                    </div>

                    @if($auditsList->isEmpty())
                        <div class="visit2026-empty" style="min-height:120px;">Aucun changement historise pour cette visite.</div>
                    @else
                        @foreach($auditsList as $audit)
                            @php
                                $oldValues = is_array($audit->old_values ?? null) ? $audit->old_values : [];
                                $newValues = is_array($audit->new_values ?? null) ? $audit->new_values : [];
                                $changeKeys = collect(array_keys($oldValues + $newValues))->unique()->values();
                            @endphp
                            <article class="visit2026-audit">
                                <div class="visit2026-audit-head">
                                    <div>
                                        <h3 class="visit2026-audit-title">{{ $text($audit->action ?? null, 'Action') }}</h3>
                                        <div class="visit2026-audit-meta">
                                            {{ $text($audit->changed_by_name ?? null, 'Utilisateur inconnu') }}
                                            @if(!empty($audit->profile_name))
                                                - {{ $audit->profile_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="visit2026-audit-meta">{{ $formatDate($audit->created_at ?? null) }}</div>
                                </div>

                                @if($changeKeys->isNotEmpty())
                                    <div class="visit2026-change-table">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Champ</th>
                                                    <th>Avant</th>
                                                    <th>Apres</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($changeKeys as $key)
                                                    <tr>
                                                        <td>{{ $key }}</td>
                                                        <td>{{ $formatValue($oldValues[$key] ?? null) }}</td>
                                                        <td>{{ $formatValue($newValues[$key] ?? null) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    @endif
                </section>
                @endif
            </div>

            <aside class="visit2026-shell">
         
                <section class="visit2026-section">
                    <div class="visit2026-section-head">
                        <div>
                            <h2 class="visit2026-section-title">Resume rapide</h2>
                            <p class="visit2026-section-note">Vue operationnelle de la visite.</p>
                        </div>
                    </div>
                    <div class="visit2026-field">
                        <span class="visit2026-label">Statut</span>
                        <strong class="visit2026-value"><span class="visit2026-status {{ $status['class'] }}">{{ $status['label'] }}</span></strong>
                    </div>
                    <div class="visit2026-field">
                        <span class="visit2026-label">Categorie</span>
                        <strong class="visit2026-value">{{ $text($visit->category ?? null) }}</strong>
                    </div>
                    <div class="visit2026-field">
                        <span class="visit2026-label">Validateur</span>
                        <strong class="visit2026-value">{{ $text($validatorName) }}</strong>
                    </div>
                    <div class="visit2026-field">
                        <span class="visit2026-label">Compte visite</span>
                        <strong class="visit2026-value">{{ $text($visit->user_username ?? null) }}</strong>
                    </div>
                </section>
            </aside>
        </div>
        </div>

        <div class="visit2026-modal" data-visit-details-modal aria-hidden="true">
            <div class="visit2026-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="visit-details-title">
                <div class="visit2026-modal-head">
                    <div>
                        <p class="visit2026-eyebrow">Informations de la visite</p>
                        <h2 id="visit-details-title" class="visit2026-title" style="font-size:22px;">{{ $fullName }}</h2>
                    </div>
                    <button type="button" class="visit2026-modal-close" data-visit-details-close aria-label="Fermer">&times;</button>
                </div>
                <div class="visit2026-modal-body">
                    <section>
                        <h3 class="visit2026-modal-section-title">Passage</h3>
                        <div class="visit2026-detail-grid">
                            @foreach($timelineRows as $row)
                                <div class="visit2026-field">
                                    <span class="visit2026-label">{{ $row['label'] }}</span>
                                    <strong class="visit2026-value">{{ $formatDate($row['value'] ?? null) }}</strong>
                                </div>
                            @endforeach
                            <div class="visit2026-field"><span class="visit2026-label">Service</span><strong class="visit2026-value">{{ $text($serviceLabel) }}</strong></div>
                            <div class="visit2026-field"><span class="visit2026-label">Badge</span><strong class="visit2026-value">{{ $text($visit->badge_n ?? null) }}</strong></div>
                        </div>
                    </section>
                    <section>
                        <h3 class="visit2026-modal-section-title">Identite visiteur</h3>
                        <div class="visit2026-detail-grid">
                            @foreach($identityRows as $row)
                                <div class="visit2026-field">
                                    <span class="visit2026-label">{{ $row['label'] }}</span>
                                    <strong class="visit2026-value">{{ $text($row['value'] ?? null) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    @if(in_array($profile, [1, 2]))
                        <section>
                            <h3 class="visit2026-modal-section-title">Destination et suivi</h3>
                            <div class="visit2026-detail-grid">
                                @foreach($destinationRows as $row)
                                    <div class="visit2026-field">
                                        <span class="visit2026-label">{{ $row['label'] }}</span>
                                        <strong class="visit2026-value">{{ $text($row['value'] ?? null) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                            <div class="visit2026-observation">
                                <span class="visit2026-label">Observations</span>
                                <div style="margin-top:6px;">{{ $text($visit->observations ?? null, 'Aucune observation.') }}</div>
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const modal = document.querySelector('[data-visit-details-modal]');
            if (!modal) return;
            const open = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };
            document.querySelectorAll('[data-visit-details-open]').forEach((button) => button.addEventListener('click', open));
            modal.querySelector('[data-visit-details-close]').addEventListener('click', close);
            modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
        })();
    </script>
@endif

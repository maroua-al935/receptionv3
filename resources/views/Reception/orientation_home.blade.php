@extends('Reception.layouts.master')

@section('body')
    <div class="space-y-5">
       

        <div id="visitx-live-dashboard" class="space-y-5" data-endpoint="{{ route('reception_live_dashboard') }}" data-status="{{ $statusFilter ?? 'all' }}">
            @include('Reception.partials.home_live')
        </div>

        <div id="orientation-modal" class="orientation-modal" aria-hidden="true">
            <div class="orientation-dialog" role="dialog" aria-modal="true" aria-label="Orienter la visite">
                <button type="button" class="orientation-close" aria-label="Fermer">&times;</button>
                <iframe class="orientation-frame" title="Orienter la visite"></iframe>
            </div>
        </div>
    </div>

    <style>
        .orientation-modal { display:none; position:fixed; inset:0; z-index:60; align-items:center; justify-content:center; padding:16px; background:rgba(15,23,42,.62); }
        .orientation-modal.is-open { display:flex; }
        .orientation-dialog { position:relative; width:min(980px,100%); height:min(850px,94vh); overflow:hidden; border-radius:18px; background:#fff; box-shadow:0 24px 70px rgba(15,23,42,.3); }
        .orientation-close { position:absolute; top:12px; right:14px; z-index:1; width:38px; height:38px; border:1px solid #e2e8f0; border-radius:10px; background:#fff; color:#475569; cursor:pointer; font-size:25px; }
        .orientation-close:hover, .orientation-close:focus-visible { color:#dc2626; outline:2px solid #fecaca; }
        .orientation-frame { width:100%; height:100%; border:0; }
    </style>

    <script>
        (() => {
            const container = document.getElementById('visitx-live-dashboard');
            const modal = document.getElementById('orientation-modal');
            const frame = modal.querySelector('.orientation-frame');
            let formLoaded = false;
            const close = () => { formLoaded = false; modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); frame.src = ''; document.body.style.overflow = ''; };

            frame.addEventListener('load', () => {
                if (!formLoaded) {
                    formLoaded = true;
                    return;
                }

                if (!frame.contentWindow.location.pathname.includes('/guests/edit/')) {
                    close();
                    window.location.reload();
                }
            });

            container.addEventListener('click', (event) => {
                const link = event.target.closest('a[href*="/guests/edit/"]');
                    if (!link) return;
                    event.preventDefault();
                    formLoaded = false;
                    frame.src = link.href;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            });
            modal.querySelector('.orientation-close').addEventListener('click', close);
            modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });

            let inFlight = false;
            async function refreshDashboard() {
                if (inFlight || document.hidden) return;
                inFlight = true;
                try {
                    const endpoint = new URL(container.dataset.endpoint, window.location.origin);
                    endpoint.searchParams.set('status', new URL(window.location.href).searchParams.get('status') || container.dataset.status || 'all');
                    const response = await fetch(endpoint, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
                    if (response.ok) {
                        const body = await response.json();
                        if (body.html) container.innerHTML = body.html;
                    }
                } finally { inFlight = false; }
            }
            window.addEventListener('focus', refreshDashboard);
            window.setInterval(refreshDashboard, 3000);
        })();
    </script>
@endsection
